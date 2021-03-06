<?php
include('../main/config.php');
$db = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8", $dbuser, $dbpass);

$hostArray = $_POST["host"];
foreach($hostArray as $key => $value) {
	if ($value == "REMOVE") unset($hostArray[$key]);
}
$sql = "CREATE temporary TABLE nessus_tmp_hosts (host_name VARCHAR(255), INDEX ndx_host_name (host_name))";
$stmt = $db->prepare($sql);
$stmt->execute();

foreach ($hostArray as $hA){
	$sql="INSERT INTO nessus_tmp_hosts (host_name) VALUES (?)";
	$stmt = $db->prepare($sql);
	$stmt->execute(array($hA));
}
$family = $_POST["family"];
$sql = "CREATE temporary TABLE nessus_tmp_family (pluginFamily VARCHAR(255), INDEX ndx_pluginFamily (pluginFamily))";
$stmt = $db->prepare($sql);
$stmt->execute();
foreach ($family as $f){
	$sql="INSERT INTO nessus_tmp_family (pluginFamily) VALUES (?)";
	$stmt = $db->prepare($sql);
	$stmt->execute(array($f));
}

$critical = $_POST["critical"];	
$high = $_POST["high"];
$medium = $_POST["medium"];
$low  = $_POST["low"];
$info = $_POST["info"];
$sArray = array($critical, $high, $medium, $low, $info);
$sql = "CREATE temporary TABLE nessus_tmp_severity (severity VARCHAR(255), INDEX ndx_severity (severity))";
$stmt = $db->prepare($sql);
$stmt->execute();
foreach ($sArray as $s){
	if($s != ""){
		$sql="INSERT INTO nessus_tmp_severity (severity) VALUES (?)";
		$stmt = $db->prepare($sql);
		$stmt->execute(array($s));
	}
}

$justVulnDB = $_POST["justVulnDB"];
$agency = $_POST["agency"];
$report_name = $_POST["report_name"];
$scan_start = $_POST["scan_start"];
$scan_end = $_POST["scan_end"];
$isVulnDB = $_POST["isVulnDB"];
date_default_timezone_set('UTC');
$myDir = "/var/www/projectRF/nessus/csvfiles/";
$myFileName = $agency . "_" . date('mdYHis') . ".csv";
$myFile = $myDir . $myFileName;
$fh = fopen($myFile, 'w') or die("can't open $myFile for writing.  Please check folder permissions.");


$sql = "SELECT DISTINCT
	nessus_results.canvas_package,
	nessus_results.bidList,
	nessus_results.certList,
	nessus_results.cveList,
	nessus_results.cvss_base_score,
	nessus_results.cvss_vector,
	nessus_results.cweList,
	nessus_results.d2_elliot_name,
	nessus_results.description,
	nessus_results.edbList,
	nessus_results.exploitability_ease,
	nessus_results.exploit_available,
	nessus_results.exploit_framework_canvas,
	nessus_results.exploit_framework_core,
	nessus_results.exploit_framework_d2_elliot,
	nessus_results.exploit_framework_metasploit,
	nessus_results.iavaList,
	nessus_results.iavbList,
	nessus_results.metasploit_name,
	nessus_results.msftList,
	nessus_results.osvdbList,
	nessus_results.patch_publication_date,
	nessus_results.pluginFamily,
	nessus_results.pluginID,
	nessus_results.plugin_modification_date,
	nessus_results.pluginName,
	nessus_results.plugin_publication_date,
	nessus_results.risk_factor,
	nessus_results.script_version,
	nessus_results.secuniaList,
	nessus_results.see_also,
	nessus_results.severity,
	nessus_results.solution,
	nessus_results.synopsis,
	nessus_results.vuln_publication_date

FROM
	nessus_results
INNER JOIN nessus_tmp_severity ON nessus_tmp_severity.severity = nessus_results.severity
INNER JOIN nessus_tmp_family ON nessus_tmp_family.pluginFamily = nessus_results.pluginFamily
WHERE
	nessus_results.agency =  ? AND
	nessus_results.report_name =  ? AND
	nessus_results.scan_start =  ? AND
	nessus_results.scan_end =  ?
";

$stmt = $db->prepare($sql);
$data = array($agency, $report_name, $scan_start, $scan_end);
$stmt->execute($data);
fwrite($fh, "\"CVSS\",\"Risk\",\"Plugin ID\",\"Name\",\"Synopsis\",\"Description\",\"Solution\",\"See Also\",\"$isVulnDB\",\"IP Address\"\n");
/*
CVE or BID
CVSS
Risk
Host
Protocol
Port
Name
Synopsis
Description
Solution
See Also
Plugin Output
*/
while($row = $stmt->fetch(PDO::FETCH_ASSOC)){

	//not all of this will end up in the CSV file but it may someday...
	//or i'll keep adding options when creating the CSV.
	
	$canvas_package = $row["canvas_package"];
    $bidList = explode(",", $row["bidList"]);
	$bidList = str_replace(",", "\n", trim($row["bidList"], ","));
    $certList = explode(",", $row["certList"]);
	$cveList = str_replace(",", "\n", trim($row["cveList"], ","));
    $cvss_base_score = $row["cvss_base_score"];
    $cvss_vector = $row["cvss_vector"];
    $cweList = explode(",", trim($row["cweList"], ","));
	$d2_elliot_name = $row["d2_elliot_name"];
    $description = $row["description"];
	$description = str_replace("&#039;", "'", $description);
    $edbList = explode(",", trim($row["edbList"], ","));
    $exploitability_ease = $row["exploitability_ease"];
    $exploit_framework_canvas = $row["exploit_framework_canvas"];
	$exploit_framework_core = $row["exploit_framework_core"];
	$exploit_framework_d2_elliot = $row["exploit_framework_d2_elliot"];
	$exploit_framework_metasploit = $row["exploit_framework_metasploit"];
    $iavaList = explode(",", trim($row["iavaList"], ","));
	$iavbList = explode(",", trim($row["iavbList"], ","));
    $metasploit_name = $row["metasploit_name"];
	$msftList = str_replace(",", "\n", trim($row["msftList"], ","));
    $osvdbList = explode(",", trim($row["osvdbList"], ","));
    $patch_publication_date = $row["patch_publication_date"];
    $pluginFamily = $row["pluginFamily"];
    $pluginID = $row["pluginID"];
    $plugin_modification_date = $row["plugin_modification_date"];
    $pluginName = str_replace("&lt;", "<", $row["pluginName"]);
    $plugin_output = $row["plugin_output"];
    $plugin_publication_date = $row["plugin_publication_date"];
    $port = $row["port"];
    $protocol = $row["protocol"];
    $risk_factor = $row["risk_factor"];
    $script_version = str_replace("$", "", $row["script_version"]);
    $secuniaList = explode(",", trim($row["secuniaList"], ","));
    //$see_alsoList = explode("\n", $row["see_also"]);
	$see_also = $row["see_also"];
    $service = $row["service"];
    $severity = $row["severity"];
    $solution = nl2br($row["solution"]);
	$solution = str_replace("&#039;", "'", $solution);
    $synopsis = str_replace("\n\n","<br>", $row["synopsis"]);
    $vuln_publication_date = $row["vuln_publication_date"];



	$vulnIDListArray = array();
	if($isVulnDB == "CVE"){
		$vulnDBList = $cveList;
	} elseif ($isVulnDB == "BID") {
		$vulnDBList = $bidList;
	} elseif ($isVulnDB == "OSVDB") {
		$vulnDBList = $osvdbList;
	} elseif ($isVulnDB == "MSFT") {
		$vulnDBList = $msftList;
	} elseif ($isVulnDB == "CWE") {
		$vulnDBList = $cweList;
	} elseif ($isVulnDB == "Secunia") {
		$vulnDBList = $secuniaList;
	} 
/*
	if($justVulnDB == "true" && !empty($vulnDBList[0])){
		foreach($vulnDBList as $vDB){
			fwrite($fh, "\"$vDB\",\"$cvss\",\"$risk_factor\",\"$ip_addr\",\"$fqdn\",\"$netbios\",\"$operating_system\",\"$protocol\",\"$port\",\"$pluginID\",\"$pluginName\",\"$synopsis\",\"$description\",\"$solution\",\"$see_also\",\"$plugin_output\"\n");
		}
	} elseif ($justVulnDB != "true") {
		foreach($vulnDBList as $vDB){
			fwrite($fh, "\"$vDB\",\"$cvss\",\"$risk_factor\",\"$ip_addr\",\"$fqdn\",\"$netbios\",\"$operating_system\",\"$protocol\",\"$port\",\"$pluginID\",\"$pluginName\",\"$synopsis\",\"$description\",\"$solution\",\"$see_also\",\"$plugin_output\"\n");
		}	
	}
*/
	fwrite($fh, "\"$cvss_base_score\",\"$risk_factor\",\"$pluginID\",\"$pluginName\",\"$synopsis\",\"$description\",\"$solution\",\"$see_also\",\"$vulnDBList\",");

	$host_sql = 	"
		SELECT DISTINCT
			nessus_tags.fqdn,
			nessus_tags.ip_addr,
			nessus_tags.netbios
		FROM
			nessus_results
		INNER JOIN nessus_tags ON nessus_results.tagID = nessus_tags.tagID
		INNER JOIN nessus_tmp_hosts ON nessus_tmp_hosts.host_name = nessus_tags.host_name
		INNER JOIN nessus_tmp_severity ON nessus_tmp_severity.severity = nessus_results.severity
		INNER JOIN nessus_tmp_family ON nessus_tmp_family.pluginFamily = nessus_results.pluginFamily
		WHERE
			nessus_results.pluginID = ? AND
			nessus_results.agency = ? AND
			nessus_results.report_name = ? AND
			nessus_results.scan_start = ? AND
			nessus_results.scan_end = ?
	";
	$data = array($pluginID, $agency, $report_name, $scan_start, $scan_end);
	$host_stmt = $db->prepare($host_sql);
	$host_stmt->execute($data);	
	$hosts = "";
	while($host_row = $host_stmt->fetch(PDO::FETCH_ASSOC)){	
		$hosts .= $host_row["ip_addr"] . "\n";
	}//end second While
	fwrite($fh, "\"$hosts\"\n");


}//end first while


/*

	nessus_tags.fqdn,
	nessus_tags.ip_addr,
	nessus_tags.mac_addr,
	nessus_tags.netbios,
	nessus_tags.operating_system

INNER JOIN nessus_tags ON nessus_results.tagID = nessus_tags.tagID
INNER JOIN nessus_tmp_hosts ON nessus_tmp_hosts.host_name = nessus_tags.host_name

	$fqdn = $row["$fqdn"];
	$ip_addr = $row["ip_addr"];
	$mac_addr = $row["mac_addr"];
	$netbios = $row["netbios"];
	$operating_system = $row["operating_system"];
	
	\"$vDB\",\"$ip_addr\"\n
*/	

?>
<html>
<head>
  <meta content="text/html; charset=ISO-8859-1"
 http-equiv="content-type">
  <title>NESSUS CSV FILE</title>
<link rel="stylesheet" type="text/css" href="../main/<?php echo "$isStyle";?>" />
<style type="text/css">
p {font-size: 90%}
a {text-decoration: none}
a:hover {text-decoration: underline}
</style>
</head>
<body>
<table width="100%"><tr>
	<td width="20%" valign="top">
	<?php include '../main/menu.php'; ?>
	</td>
	<td valign="top">
		<hr>
		<p align="center"><a href="csvfiles/<?php echo "$myFileName";?>">Click Here</a> to download the CSV file.</p>
		<hr>
	</td>
</tr></table>
</body>
</html>