<?php
/** Modified by Tuan on the 9th of October 2021 to allow switching between stripe accounts
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2021 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

$db  = Factory::getDbo();
$fields = array_keys($db->getTableColumns('#__eb_events'));

if (!in_array('stripe_account', $fields))
{
	$sql = "ALTER TABLE  `#__eb_events` ADD `stripe_account` VARCHAR( 100 ) NULL;;";
	$db->setQuery($sql)
		->execute();
}

$accounts = require JPATH_ROOT . '/administrator/components/com_eventbooking/stripeaccounts.php';

$options   = [];
$options[] = HTMLHelper::_('select.option', '', Text::_('Default Account'));

foreach ($accounts as $key => $account)
{
	$options[] = HTMLHelper::_('select.option', $key, $account['title'] ?? $key);
}
?>
	<div class="control-group">
		<div class="control-label">
			<?php echo  Text::_('Stripe Account'); ?>
		</div>
		<div class="controls">
			<?php echo HTMLHelper::_('select.genericlist', $options, 'stripe_account', 'class="form-select"', 'value', 'text', $this->item->stripe_account); ?>
		</div>
	</div>
<?php
