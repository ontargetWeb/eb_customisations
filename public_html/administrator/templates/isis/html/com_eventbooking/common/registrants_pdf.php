<?php

/**
 * public_html/administrator/templates/isis/html/com_eventbooking/common/registrants_pdf.php 
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2021 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

  
  
defined('_JEXEC') or die;
$config = EventbookingHelper::getConfig();

$i = 1;


?>
  

  
<table border="0" width="100%" cellspacing="0" cellpadding="2">
<tr>
				<td width="50%" style="text-align: center;">
				    <p style="padding-bottom: 20px;">
				    <img src="images/assets/logo.png" alt="logo"/></p></td>
				<td width="50%" style="text-align: left;"><?php
$first = true;
foreach ( $rows as $row )
{
    if ( $first )
    {?>
<p style="padding-bottom: 20px; text-align: center;">
<?php 
echo "<h1> $row->title </h1>"; 
echo "<br>Date / Time: $row->event_date"; 


$db = JFactory::getDbo();
$query = $db
    ->getQuery(true)
    ->select('location_id')
    ->from($db->quoteName('#__eb_events'))
    ->where($db->quoteName('id') . " = " . $db->quote($row->event_id));

$db->setQuery($query);
$result = $db->loadResult();

$query2 = $db
    ->getQuery(true)
    ->select('name')
    ->from($db->quoteName('#__eb_locations'))
    ->where($db->quoteName('id') . " = " . $db->quote($result));

$db->setQuery($query2);
$result = $db->loadResult();


echo "<br>Venue: $result";
$registry = new JRegistry($row->custom_fields);
echo "<br>Tutor:"; echo $registry->get('field_speaker');
$first = false;
    }
    
    ?>

</p>

<?php
break;

}

?>
</td>
</tr>
</table>
<p style="padding-bottom: 20px; text-align: center;">
<hr/>
</p>

  
  


<table width="100%" cellspacing="0" cellpadding="3" border="0">
   
	<thead>
		<tr style="font-weight: bold; text-align: left; background-color:#fcfcfc;color:#000;">
			<th width="5%" height="20">
				No
			</th>
			<th width="5%" height="20">
				<?php echo JText::_('EB_ID'); ?>
			</th>
			<th height="20" width="10%">
				<?php echo JText::_('EB_FIRST_NAME'); ?>
			</th height="20">
			<th height="20" width="10%">
				<?php echo JText::_('EB_LAST_NAME'); ?>
			</th height="20">
		
			<th height="20" width="10%">
				Type
			</th>

			<th height="20" width="10%">
				School Roll
			</th>
			<th width="20%" height="20">
				School Name
			</th>
			<th width="30%" height="20">
				Signature
			</th>
			
		</tr>
	</thead>
                  <br>
	<tbody>
	<?php
		foreach ($rows as $row)
		{
		?>
		
			<tr>
				<td width="5%" height="20"><?php echo $i++; ?></td>
				<td width="5%" height="20"><?php echo $row->id; ?></td>
				<td width="10%" height="20"><?php echo $row->first_name; ?></td>
				<td width="10%" height="20"><?php echo $row->last_name; ?></td>
				<td width="10%" height="20"><?php echo $row->eb_teachinglevel; ?></td>
				<td width="10%" height="20"><?php echo $row->cb_schoolroll; ?></td>
				<td width="20%" height="20"><?php echo $row->eb_School; ?></td>
				<td width="30%" height="20"><?php echo " " ?><hr/></td>
				
			</tr>
		<?php
		}
	?>
	</tbody>
</table>
<p style="padding-bottom: 20px; text-align: center;">
<hr/>
</p>
