<?php
/**
 * @version            3.10.2
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2018 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

/**
 * Stripe payment plugin for Events Booking
 * Modified by Tuan on the 9th of October 2021 to allow switching between stripe accounts
 * Notes - includes function to switch between accounts in administrator/components/com_eventbooking/stripeaccounts.php
 * Modifications to administrator/components/com_eventbooking/view/event/tmpl/default_custom_settings.php 
 * @author Tuan Pham Ngoc
 *
 */
class os_stripecheckout extends RADPayment
{

	/**
	 * Constructor
	 *
	 * @param   JRegistry  $params
	 * @param   array      $config
	 */
	public function __construct($params, $config = array())
	{
		// Use sandbox API keys if available
		if (!$params->get('mode', 1))
		{
			if ($params->get('sandbox_stripe_public_key'))
			{
				$params->set('stripe_public_key', $params->get('sandbox_stripe_public_key'));
			}

			if ($params->get('sandbox_stripe_api_key'))
			{
				$params->set('stripe_api_key', $params->get('sandbox_stripe_api_key'));
			}
		}

		$view = Factory::getApplication()->input->getCmd('view', '');
		$id   = Factory::getApplication()->input->getInt('id', 0);

		if ($view === 'register')
		{
			$event = EventbookingHelperDatabase::getEvent($id);

			if ($event->stripe_account)
			{
				$account = $this->getStripeAccount($event->stripe_account);

				if ($account !== false)
				{
					$params->set('stripe_public_key', $account['public_api_key']);
					$params->set('stripe_api_key', $account['secret_api_key']);
				}
			}
		}

		parent::__construct($params, $config);
	}

	/**
	 * Process payment
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   array                        $data
	 */
	public function processPayment($row, $data)
	{
		$this->loadStripeLib($row);

		$Itemid  = JFactory::getApplication()->input->getInt('Itemid', 0);
		$siteUrl = JUri::base();

		if ($row->process_deposit_payment)
		{
			if (JPluginHelper::isEnabled('system', 'cache'))
			{
				$returnUrl = $siteUrl . 'index.php?option=com_eventbooking&view=payment&layout=complete&Itemid=' . $Itemid . '&pt=' . time();
			}
			else
			{
				$returnUrl = $siteUrl . 'index.php?option=com_eventbooking&view=payment&layout=complete&Itemid=' . $Itemid;
			}
		}
		else
		{
			$returnUrl = $siteUrl . 'index.php?option=com_eventbooking&view=complete&Itemid=' . $Itemid;
		}

		$cancelUrl = $siteUrl . 'index.php?option=com_eventbooking&view=cancel&layout=default&id=' . $row->id . '&Itemid=' . $Itemid;

		// Meta data

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('name, title')
			->from('#__eb_fields')
			->where('published = 1');
		$db->setQuery($query);
		$fields = $db->loadObjectList('name');

		$metaData[$fields['first_name']->title] = $row->first_name;

		if ($row->last_name)
		{
			$metaData[$fields['last_name']->title] = $row->last_name;
		}

		$metaData['Email']  = $row->email;
		$metaData['Source'] = 'Event Booking';
		$metaData['Event']  = $data['event_title'];

		if ($row->user_id > 0)
		{
			$metaData['User ID'] = $row->user_id;
		}

		$metaData['Registrant ID'] = $row->id;

		try
		{
			$session = \Stripe\Checkout\Session::create([
				'payment_method_types' => $this->params->get('payment_method_types', ['card']),
				'line_items'           => [
					[
						'name'     => $data['item_name'],
						'amount'   => 100 * round($data['amount'], 2),
						'currency' => $data['currency'],
						'quantity' => 1,
					],
				],
				'payment_intent_data'  => [
					'description' => $data['item_name'],
					'metadata'    => $metaData,
				],
				'success_url'          => $returnUrl,
				'cancel_url'           => $cancelUrl,
				'client_reference_id'  => $this->params->get('order_prefix', 'EB') . $row->id,
				'customer_email'       => $row->email,
			]);

			$this->redirectToStripe($session->id);
		}
		catch (Exception $e)
		{
			JFactory::getSession()->set('omnipay_payment_error_reason', $e->getMessage());

			JFactory::getApplication()->redirect(JRoute::_('index.php?option=com_eventbooking&view=failure&Itemid=' . $Itemid,
				false));
		}
	}

	public function verifyPayment()
	{
		if (!$this->validate())
		{
			return;
		}

		$id = $this->notificationData['id'];

		$row = JTable::getInstance('Registrant', 'EventbookingTable');

		if (!$row->load($id))
		{
			$this->logGatewayData('Invalid Record ID:' . $id);

			return false;
		}

		if ($row->published == 1 && $row->payment_status)
		{
			$this->logGatewayData('Invalid Status, already published before');

			return false;
		}

		$this->onPaymentSuccess($row, $this->notificationData['transaction_id']);
	}

	/**
	 * Refund a transaction
	 *
	 * @param   EventbookingTableRegistrant  $row
	 *
	 * @throws Exception
	 */
	public function refund($row)
	{
		$this->loadStripeLib($row);

		try
		{
			\Stripe\Refund::create(['charge' => $row->transaction_id]);
		}
		catch (\Stripe\Error\Card $e)
		{

			// Use the variable $error to save any errors
			// To be displayed to the customer later in the page
			$body  = $e->getJsonBody();
			$err   = $body['error'];
			$error = $err['message'];

			throw new Exception($error);
		}
	}

	/**
	 *
	 */
	protected function validate()
	{
		$row = new stdClass;

		$payload = @file_get_contents('php://input');
		$this->logGatewayData($payload);

		$event_json = json_decode($payload);

		if ($event_json->data->object->client_reference_id)
		{
			$clientReferenceId = $event_json->data->object->client_reference_id;

			$orderPrefix = $this->params->get('order_prefix', 'EB');

			if ($orderPrefix && strpos($clientReferenceId, $orderPrefix) === false)
			{
				return false;
			}

			$id = (int) substr($clientReferenceId, strlen($orderPrefix));

			// Try to get subscription record for this subject
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('*')
				->from('#__eb_registrants')
				->where('id = ' . $id);
			$db->setQuery($query);
			$row = $db->loadObject();
		}

		$this->loadStripeLib($row);
		// Try to get Singing Secret from the used account


		$endpoint_secret = $this->params->get('endpoint_secret');

		$payload    = @file_get_contents('php://input');
		$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
		$event      = null;

		try
		{
			$event = \Stripe\Webhook::constructEvent(
				$payload, $sig_header, $endpoint_secret
			);
		}
		catch (\UnexpectedValueException $e)
		{
			// Invalid payload
			http_response_code(400); // PHP 5.4 or greater

			return false;
		}
		catch (\Stripe\Error\SignatureVerification $e)
		{
			// Invalid signature
			http_response_code(400); // PHP 5.4 or greater

			return false;
		}

		if ($event->type == 'checkout.session.completed')
		{

			$session       = $event->data->object;
			$paymentIntent = \Stripe\PaymentIntent::retrieve($session->payment_intent);

			$orderPrefix = $this->params->get('order_prefix', 'EB');

			if ($orderPrefix && strpos($session->client_reference_id, $orderPrefix) === false)
			{
				return false;
			}

			$this->notificationData['id'] = (int) substr($session->client_reference_id, strlen($orderPrefix));

			$transactionId = '';

			foreach ($paymentIntent->charges as $charge)
			{
				$transactionId = $charge->id;
			}

			$this->notificationData['transaction_id'] = $transactionId;

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Load Stripe library, set API Key and make the API ready to be used
	 *
	 * @var EventbookingTableRegistrant $row
	 */
	protected function loadStripeLib($row)
	{
		if (!class_exists('\Stripe\Stripe'))
		{
			require_once JPATH_ROOT . '/components/com_eventbooking/payments/stripecheckout/init.php';
		}

		if (!empty($row->event_id))
		{
			$event = EventbookingHelperDatabase::getEvent($row->event_id);

			if ($event->stripe_account)
			{
				$account = $this->getStripeAccount($event->stripe_account);

				if ($account !== false)
				{
					$this->params->set('stripe_public_key', $account['public_api_key']);
					$this->params->set('stripe_api_key', $account['secret_api_key']);
					$this->params->set('endpoint_secret', $account['signing_secret']);
				}
			}
		}

		\Stripe\Stripe::setApiKey($this->params->get('stripe_api_key'));
	}

	/**
	 * Redirect users to Stripe for processing payment
	 *
	 * @param $sessionId
	 */
	protected function redirectToStripe($sessionId)
	{
		//Get redirect heading
		$language    = JFactory::getLanguage();
		$languageKey = 'EB_WAIT_' . strtoupper(substr($this->name, 3));

		if ($language->hasKey($languageKey))
		{
			$redirectHeading = JText::_($languageKey);
		}
		else
		{
			$redirectHeading = JText::sprintf('EB_REDIRECT_HEADING', $this->getTitle());
		}
		?>
        <div class="payment-heading"><?php echo $redirectHeading; ?></div>
        <script src="https://js.stripe.com/v3/"></script>
        <script type="text/javascript">
            var stripe = Stripe('<?php echo $this->params->get('stripe_public_key'); ?>');

            stripe.redirectToCheckout({
                // Make the id field from the Checkout Session creation API response
                // available to this file, so you can provide it as parameter here
                // instead of the {{CHECKOUT_SESSION_ID}} placeholder.
                sessionId: '<?php echo $sessionId; ?>'
            }).then(function (result) {
                // If `redirectToCheckout` fails due to a browser or network
                // error, display the localized error message to your customer
                // using `result.error.message`.
                alert(result.error.message);
            });
        </script>
		<?php
	}

	/**
	 * Get the stripe account data base on account ID
	 *
	 * @param $account
	 *
	 * @return bool|mixed
	 */
	private function getStripeAccount($account)
	{
		$accounts = require JPATH_ROOT . '/administrator/components/com_eventbooking/stripeaccounts.php';

		if (isset($accounts[$account]))
		{
			return $accounts[$account];
		}

		return false;
	}
}
