<?php
  


                    	
//User Details
        
//Not working with TCPDF
echo "<table style='width:100%; border-bottom: 1px solid grey;'>";
echo "<thead style='background:#ccc; padding:10px;'>";
echo "<tr>";
echo "<th>#</th>";
echo "<th>ID</th>";
echo "<th>First Name</th>";
echo "<th>Last Name</th>";
echo "<th>Type</th>";
echo "<th>School Roll </th>";
echo "<th>School Name </th>";
echo "<th>Signature</th>";
echo "</tr>";
echo "</thead>";
                
                    
foreach ($rows as $row) {
	echo "<tr>";

echo "<td style='border-bottom: 1px solid grey;'>". $i++ . "</td>";
echo "<td style='border-bottom: 1px solid grey;'>". $row->id . "</td>";
echo "<td style='border-bottom: 1px solid grey;'>". $row->first_name . "</td>";
echo "<td style='border-bottom: 1px solid grey;'>". $row->last_name . "</td>";
echo "<td style='border-bottom: 1px solid grey;'>". $row->eb_teachinglevel . "</td>";
echo "<td style='border-bottom: 1px solid grey;'>". $row->cb_schoolroll . "</td>";
echo "<td style='border-bottom: 1px solid grey;'>". $row->eb_School . "</td>";
echo "<td width='33%' style='border-bottom: 1px solid grey;'>"."</td>";
                    	
                    	echo "</tr>";
                    	
                }
                echo "</table>";
                     
                    
            
            ?>  
