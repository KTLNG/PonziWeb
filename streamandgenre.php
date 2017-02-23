<?php
error_reporting(E_ERROR | E_PARSE);

$the_server="";
$shoutcastv2_filename="/index.html?sid=1";
$the_stats_file="/status.xsl";
$is_shoutcast=true;
$streamtitle="";
$streamgenre="";
if (!isset($_GET['translateAllRadioStations'])) {
	$translateAllRadioStations="ALL RADIO STATIONS";
} else {
	$translateAllRadioStations=$_GET['translateAllRadioStations'];
}



if (!isset($_GET['the_stream']) || !isset($_GET['cur_i'])) {
			//nothing
			die();
} else {
	
		$the_link=trim($_GET['the_stream']);
		if (strpos($the_link,'radionomy.com')) { //is a radionomy.com link
			$streamgenre=$translateAllRadioStations;
			preg_match ( '#^http://(.*)/(.*)#' , $the_link,  $matches);
			$streamtitle=$matches[2];
		} else {
					
					preg_match ( '#^http://(.*):(.*)/|;#' , $the_link,  $matches);
					$ip=$matches[1];
					$port=$matches[2];
					$the_server='http://'.$ip.':'.$port;

					//echo $ip.'  ---  '.$port.'  ---  ';
		
					//shoutcast start
					$fp = @fsockopen($ip,$port,$errno,$errstr,1);
					if (!$fp) { 
						$streamtitle = "Connection timed out or the server is offline"; // If you always get this error then it means your web host is blocking outward connections. If you ask nicely, they might add your server IP to their allow list.
						//$is_shoutcast=false;
					} else {
						@fputs($fp, "GET /index.html HTTP/1.0\r\nUser-Agent: Mozilla\r\n\r\n");
						while (!@feof($fp)) {
							$info = @fgets($fp);
						}
						if (stripos($info,'<title>Shoutcast') || stripos($info,'<title>Redirect</title>')) { //Is shoutcas ?
							if (stripos($info,'<title>Redirect</title>')) { //is shoutcastv2?
								$info = '';
								$fp = @fopen($the_server.$shoutcastv2_filename,'r');
								if(!$fp) {
								 	//error connecting to server!
									 $streamtitle = "Unable to connect to Shoutcast V2 server.";
							  	} else {
									while(!@feof($fp)) {
									   $info .= @fread($fp,1024);
									}
									
									@fclose($fp);								
									
									$split = explode('Stream Name: </td><td>', $info);
									//echo "v2: ".is_array($split)."   --   ".count($split)."   --   ".$split[1]."   --   ";
									if (count($split)>=2) { // is shoutcast v2
										$split = explode('</td><td>', $split[1]);
										$split = explode('</td></tr>', $split[0]);
										$streamtitle = $split[0];
										//the genre
										$split_genre = explode('Stream Genre: </td><td>', $info);
										$split_genre = explode('</b></td></tr>', $split_genre[1]);
										$streamgenre = $split_genre[0];	
									}
								}
								//is shoutcastv2 end
							} else { //is shoutcastv1
								$info = str_replace('</body></html>', "", $info);
								$split = explode('Stream Title: </font></td><td><font class=default><b>', $info);
								if (count($split)>=2) { // is shoutcast v1
									$split = explode('</b></td></tr><tr>', $split[1]);
									$streamtitle = $split[0];
									//the genre
									$split_genre = explode('Stream Genre: </font></td><td><font class=default><b>', $info);
									$split_genre = explode('</b></td></tr>', $split_genre[1]);
									$streamgenre = $split_genre[0];
								}								
							}
							//is shoutcastv1 end
						} else { // Then is icecast
							  //get statistics file contents
							  $fp = @fopen($the_server.$the_stats_file,'r');
							  if(!$fp) {
								 //error connecting to server!
								 $streamtitle = "Unable to connect to Icecast server.";
							  } else {
								  $stats_file_contents = '';
								  while(!@feof($fp)) {
									 $stats_file_contents .= @fread($fp,1024);
								  }
								  
								  @fclose($fp);
								  
								  //the mount_point start
								  preg_match ( '#^http://(.*):(.*)/(.*)#' , $the_link,  $matches);
								  $mount_point=$matches[3];
								  $search_start_point=0;
								  if ($mount_point!=';')
									  $search_start_point=strpos ($stats_file_contents,$mount_point);
								  //the mount_point end
								  /////echo "Then is icecast: $search_start_point <br>";	
								  if ($search_start_point>0)
									  $stats_file_contents=substr ( $stats_file_contents,$search_start_point );									  
							  
								  //GENERAL ICECAST TYPE START < 2.4.0
								  //create array to store results for later usage
								  $radio_info = array();
								  $radio_info['server'] = $the_server;
								  $radio_info['title'] = '';
								  $radio_info['description'] = '';
								  $radio_info['content_type'] = '';
								  $radio_info['mount_start'] = '';
								  $radio_info['bit_rate'] = '';
								  $radio_info['listeners'] = '';
								  $radio_info['most_listeners'] = '';
								  $radio_info['genre'] = '';
								  $radio_info['url'] = '';
								  $radio_info['now_playing'] = array();
									 $radio_info['now_playing']['artist'] = '';
									 $radio_info['now_playing']['track'] = '';
								  
								  $temp = array();
								  
								  //format results into array
								  $search_for = "<td\s[^>]*class=\"streamdata\">(.*)<\/td>";
								  $search_td = array('<td class="streamdata">','</td>');
								  
								  if(preg_match_all("/$search_for/siU",$stats_file_contents,$matches)) {
									 foreach($matches[0] as $match) {
										$to_push = str_replace($search_td,'',$match);
										$to_push = trim($to_push);
										array_push($temp,$to_push);
									 }
								  }
								  
								  //build final array from temp array
								  $radio_info['title'] = $temp[0];
								  $radio_info['description'] = $temp[1];
								  $radio_info['content_type'] = $temp[2];
								  $radio_info['mount_start'] = $temp[3];
								  $radio_info['bit_rate'] = $temp[4];
								  $radio_info['listeners'] = $temp[5];
								  $radio_info['most_listeners'] = $temp[6];
								  $radio_info['genre'] = $temp[7];
								  $radio_info['url'] = $temp[8];
								  
								  $streamtitle=$radio_info['title'];
								  $streamgenre=$radio_info['genre'];
								  //GENERAL ICECAST TYPE END < 2.4.0
								  
								  
								  if (trim($streamtitle)!='') {
									  //nothing
								  } else { //ICECAST TYPE from v2.4.0 and up
									/*$split=explode('</td><td',$stats_file_contents);
									//print_r($split);
									if (count($split)>=2) {
										preg_match('/>(.*)</', $split[1], $matches);
										$streamtitle=substr($matches[0],1,strlen($matches[0]));
									}
									if (count($split)>=9) {
										preg_match('/>(.*)</', $split[8], $matches);
										$streamgenre=substr($matches[0],1,strlen($matches[0]));
									}		*/
									
										$stats_file_contents = '';
										$new_stats_file_contents = '';
										$the_stats_file="/status-json.xsl";
										//get statistics file contents
										$fp = @fopen($the_server.$the_stats_file,'r');
		
										if(!$fp) {
										   //error connecting to server!
										  $streamtitle = "Unable to connect to Icecast 2.4 server.";
										} else {
										
												$stats_file_contents = '';
												
												while(!@feof($fp)) {
												   $stats_file_contents .= @fread($fp,1024);
												}
												
												@fclose($fp);
												$search_start_point=stripos($stats_file_contents,',"server_name"');
												if ($search_start_point>0) {
													$new_stats_file_contents=substr ( $stats_file_contents,$search_start_point );
													$search_end_point=stripos($new_stats_file_contents,'","server_type"');
													$new_stats_file_contents=substr ( $new_stats_file_contents,16, $search_end_point-16);
													$streamtitle = $new_stats_file_contents;
												}
												
												$search_start_point=stripos($stats_file_contents,',"genre"');
												if ($search_start_point>0) {
													$new_stats_file_contents=substr ( $stats_file_contents,$search_start_point );
													$search_end_point=stripos($new_stats_file_contents,'","listener_peak"');
													$new_stats_file_contents=substr ( $new_stats_file_contents,10, $search_end_point-10);
													$streamgenre = $new_stats_file_contents;
												}										}																
									
									
								  }
							  }
							  //is icecast end
						}

					}

					
					

					if (trim($streamtitle)!='') {
						//nothing
					} else {
						$streamtitle='The stream title is currently empty';
					}/**/
					
					if (trim($streamgenre)!='') {
						$streamgenre=str_replace ( "," , ";" , $streamgenre);
					} else {
						//$streamgenre='No genre available';
					}
					
					$streamgenre=$translateAllRadioStations.";".$streamgenre;
					
					//echo $_GET['cur_i'].'#----#'.'http://'.$ip.':'.$port.$the_stats_file.count($split).' ----  '.$is_shoutcast;
		}
			echo $_GET['cur_i'].'#----#'.strip_tags($streamtitle).'#----#'.strip_tags($streamgenre);
}
?>