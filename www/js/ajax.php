<?php
if (isset($_REQUEST['AssayId'])) {

    echo "čus";
}

if (isset($_REQUEST['source'])) {

    if ($_REQUEST['source'] != "MANUAL") {
        
        if ($_REQUEST['source'] == "DEMO") {
            ?>
            <h4>Optická denzita / Optical density:</h4>
            <table id="table-od" width="100%" >
                <tr><th></th><th>1.</th><th>2.</th><th>3.</th><th>4.</th><th>5.</th><th>6.</th><th>7.</th><th>8.</th><th>9.</th><th>10.</th><th>11.</th><th>12.</th></tr>
                <tr>
                    <th>A</th>
                    <td><input tabindex="101" type="text" name="abs[1]" size="3" class="form-control" value="0,054" /></td>
                    <td><input tabindex="109" type="text" name="abs[2]" size="3" class="form-control" value="0,125" /></td>
                    <td><input tabindex="117" type="text" name="abs[3]" size="3" class="form-control" value="0,357" /></td>
                    <td><input tabindex="125" type="text" name="abs[4]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="133" type="text" name="abs[5]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="141" type="text" name="abs[6]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="149" type="text" name="abs[7]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="157" type="text" name="abs[8]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="165" type="text" name="abs[9]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="173" type="text" name="abs[10]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="181" type="text" name="abs[11]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="189" type="text" name="abs[12]" size="3" class="form-control" value="" /></td>
                </tr>
                <tr>
                    <th>B</th>
                    <td><input tabindex="102" type="text" name="abs[13]" size="3" class="form-control" value="0,736" /></td>
                    <td><input tabindex="110" type="text" name="abs[14]" size="3" class="form-control" value="0,789" /></td>
                    <td><input tabindex="118" type="text" name="abs[15]" size="3" class="form-control" value="0,657" /></td>
                    <td><input tabindex="126" type="text" name="abs[16]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="134" type="text" name="abs[17]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="142" type="text" name="abs[18]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="150" type="text" name="abs[19]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="158" type="text" name="abs[20]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="166" type="text" name="abs[21]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="174" type="text" name="abs[22]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="182" type="text" name="abs[23]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="190" type="text" name="abs[24]" size="3" class="form-control" value="" /></td>
                </tr>
                <tr>
                    <th>C</th>
                    <td><input tabindex="103" type="text" name="abs[25]" size="3" class="form-control" value="0,740" /></td>
                    <td><input tabindex="111" type="text" name="abs[26]" size="3" class="form-control" value="0,175" /></td>
                    <td><input tabindex="119" type="text" name="abs[27]" size="3" class="form-control" value="0,012" /></td>
                    <td><input tabindex="127" type="text" name="abs[28]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="135" type="text" name="abs[29]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="143" type="text" name="abs[30]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="151" type="text" name="abs[31]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="159" type="text" name="abs[32]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="167" type="text" name="abs[33]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="175" type="text" name="abs[34]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="183" type="text" name="abs[35]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="191" type="text" name="abs[36]" size="3" class="form-control" value="" /></td>
                </tr>
                <tr>
                    <th>D</th>
                    <td><input tabindex="104" type="text" name="abs[37]" size="3" class="form-control" value="1,123" /></td>
                    <td><input tabindex="112" type="text" name="abs[38]" size="3" class="form-control" value="0,218" /></td>
                    <td><input tabindex="120" type="text" name="abs[39]" size="3" class="form-control" value="0,100" /></td>
                    <td><input tabindex="128" type="text" name="abs[40]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="136" type="text" name="abs[41]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="144" type="text" name="abs[42]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="152" type="text" name="abs[43]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="160" type="text" name="abs[44]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="168" type="text" name="abs[45]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="176" type="text" name="abs[46]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="184" type="text" name="abs[47]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="192" type="text" name="abs[48]" size="3" class="form-control" value="" /></td>
                </tr>
                <tr>
                    <th>E</th>
                    <td><input tabindex="105" type="text" name="abs[49]" size="3" class="form-control" value="0,123" /></td>
                    <td><input tabindex="113" type="text" name="abs[50]" size="3" class="form-control" value="0,354" /></td>
                    <td><input tabindex="121" type="text" name="abs[51]" size="3" class="form-control" value="1,892" /></td>
                    <td><input tabindex="129" type="text" name="abs[52]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="137" type="text" name="abs[53]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="145" type="text" name="abs[54]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="153" type="text" name="abs[55]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="161" type="text" name="abs[56]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="169" type="text" name="abs[57]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="177" type="text" name="abs[58]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="185" type="text" name="abs[59]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="193" type="text" name="abs[60]" size="3" class="form-control" value="" /></td>
                </tr>
                <tr>
                    <th>F</th>
                    <td><input tabindex="106" type="text" name="abs[61]" size="3" class="form-control" value="0,111" /></td>
                    <td><input tabindex="114" type="text" name="abs[62]" size="3" class="form-control" value="2,521" /></td>
                    <td><input tabindex="122" type="text" name="abs[63]" size="3" class="form-control" value="0,055" /></td>
                    <td><input tabindex="130" type="text" name="abs[64]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="138" type="text" name="abs[65]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="146" type="text" name="abs[66]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="154" type="text" name="abs[67]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="162" type="text" name="abs[68]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="170" type="text" name="abs[69]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="178" type="text" name="abs[70]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="186" type="text" name="abs[71]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="194" type="text" name="abs[72]" size="3" class="form-control" value="" /></td>
                </tr>
                <tr>
                    <th>G</th>
                    <td><input tabindex="107" type="text" name="abs[73]" size="3" class="form-control" value="0,222" /></td>
                    <td><input tabindex="115" type="text" name="abs[74]" size="3" class="form-control" value="5,234" /></td>
                    <td><input tabindex="123" type="text" name="abs[75]" size="3" class="form-control" value="0,958" /></td>
                    <td><input tabindex="131" type="text" name="abs[76]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="139" type="text" name="abs[77]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="147" type="text" name="abs[78]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="155" type="text" name="abs[79]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="163" type="text" name="abs[80]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="171" type="text" name="abs[81]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="179" type="text" name="abs[82]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="187" type="text" name="abs[83]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="195" type="text" name="abs[84]" size="3" class="form-control" value="" /></td>
                </tr>
                <tr>
                    <th>H</th>
                    <td><input tabindex="108" type="text" name="abs[85]" size="3" class="form-control" value="0,333" /></td>
                    <td><input tabindex="116" type="text" name="abs[86]" size="3" class="form-control" value="0,005" /></td>
                    <td><input tabindex="124" type="text" name="abs[87]" size="3" class="form-control" value="0,010" /></td>
                    <td><input tabindex="132" type="text" name="abs[88]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="140" type="text" name="abs[89]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="148" type="text" name="abs[90]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="156" type="text" name="abs[91]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="164" type="text" name="abs[92]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="172" type="text" name="abs[93]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="180" type="text" name="abs[94]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="188" type="text" name="abs[95]" size="3" class="form-control" value="" /></td>
                    <td><input tabindex="196" type="text" name="abs[96]" size="3" class="form-control" value="" /></td>
                </tr>
            </table>
            <?php
            
        } else {
            ?>
            <label>Vložit soubor / Load file:</label><br>
            <input type="File" name="File" class="form-control" required />
            <?php
        }
    } else {
        ?>
        <h4>Optická denzita / Optical density:</h4>
        <table id="table-od" width="100%" >
            <tr><th></th><th>1.</th><th>2.</th><th>3.</th><th>4.</th><th>5.</th><th>6.</th><th>7.</th><th>8.</th><th>9.</th><th>10.</th><th>11.</th><th>12.</th></tr>
            <tr>
                <th>A</th>
                <td><input tabindex="101" type="text" name="abs[1]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="109" type="text" name="abs[2]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="117" type="text" name="abs[3]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="125" type="text" name="abs[4]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="133" type="text" name="abs[5]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="141" type="text" name="abs[6]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="149" type="text" name="abs[7]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="157" type="text" name="abs[8]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="165" type="text" name="abs[9]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="173" type="text" name="abs[10]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="181" type="text" name="abs[11]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="189" type="text" name="abs[12]" size="3" class="form-control" value="" /></td>
            </tr>
            <tr>
                <th>B</th>
                <td><input tabindex="102" type="text" name="abs[13]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="110" type="text" name="abs[14]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="118" type="text" name="abs[15]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="126" type="text" name="abs[16]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="134" type="text" name="abs[17]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="142" type="text" name="abs[18]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="150" type="text" name="abs[19]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="158" type="text" name="abs[20]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="166" type="text" name="abs[21]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="174" type="text" name="abs[22]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="182" type="text" name="abs[23]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="190" type="text" name="abs[24]" size="3" class="form-control" value="" /></td>
            </tr>
            <tr>
                <th>C</th>
                <td><input tabindex="103" type="text" name="abs[25]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="111" type="text" name="abs[26]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="119" type="text" name="abs[27]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="127" type="text" name="abs[28]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="135" type="text" name="abs[29]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="143" type="text" name="abs[30]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="151" type="text" name="abs[31]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="159" type="text" name="abs[32]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="167" type="text" name="abs[33]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="175" type="text" name="abs[34]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="183" type="text" name="abs[35]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="191" type="text" name="abs[36]" size="3" class="form-control" value="" /></td>
            </tr>
            <tr>
                <th>D</th>
                <td><input tabindex="104" type="text" name="abs[37]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="112" type="text" name="abs[38]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="120" type="text" name="abs[39]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="128" type="text" name="abs[40]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="136" type="text" name="abs[41]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="144" type="text" name="abs[42]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="152" type="text" name="abs[43]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="160" type="text" name="abs[44]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="168" type="text" name="abs[45]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="176" type="text" name="abs[46]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="184" type="text" name="abs[47]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="192" type="text" name="abs[48]" size="3" class="form-control" value="" /></td>
            </tr>
            <tr>
                <th>E</th>
                <td><input tabindex="105" type="text" name="abs[49]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="113" type="text" name="abs[50]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="121" type="text" name="abs[51]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="129" type="text" name="abs[52]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="137" type="text" name="abs[53]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="145" type="text" name="abs[54]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="153" type="text" name="abs[55]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="161" type="text" name="abs[56]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="169" type="text" name="abs[57]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="177" type="text" name="abs[58]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="185" type="text" name="abs[59]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="193" type="text" name="abs[60]" size="3" class="form-control" value="" /></td>
            </tr>
            <tr>
                <th>F</th>
                <td><input tabindex="106" type="text" name="abs[61]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="114" type="text" name="abs[62]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="122" type="text" name="abs[63]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="130" type="text" name="abs[64]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="138" type="text" name="abs[65]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="146" type="text" name="abs[66]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="154" type="text" name="abs[67]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="162" type="text" name="abs[68]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="170" type="text" name="abs[69]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="178" type="text" name="abs[70]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="186" type="text" name="abs[71]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="194" type="text" name="abs[72]" size="3" class="form-control" value="" /></td>
            </tr>
            <tr>
                <th>G</th>
                <td><input tabindex="107" type="text" name="abs[73]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="115" type="text" name="abs[74]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="123" type="text" name="abs[75]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="131" type="text" name="abs[76]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="139" type="text" name="abs[77]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="147" type="text" name="abs[78]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="155" type="text" name="abs[79]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="163" type="text" name="abs[80]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="171" type="text" name="abs[81]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="179" type="text" name="abs[82]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="187" type="text" name="abs[83]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="195" type="text" name="abs[84]" size="3" class="form-control" value="" /></td>
            </tr>
            <tr>
                <th>H</th>
                <td><input tabindex="108" type="text" name="abs[85]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="116" type="text" name="abs[86]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="124" type="text" name="abs[87]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="132" type="text" name="abs[88]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="140" type="text" name="abs[89]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="148" type="text" name="abs[90]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="156" type="text" name="abs[91]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="164" type="text" name="abs[92]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="172" type="text" name="abs[93]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="180" type="text" name="abs[94]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="188" type="text" name="abs[95]" size="3" class="form-control" value="" /></td>
                <td><input tabindex="196" type="text" name="abs[96]" size="3" class="form-control" value="" /></td>
            </tr>
        </table>
        <?php
    }
}
?>
