<?php
/*

Now Playing PHP script for SHOUTcast

This script is (C) MixStream.net 2008

Feel free to modify this free script 
in any other way to suit your needs.

Version: v1.1

*/


/* ----------- Server configuration ---------- */
$songtitle="";
if (!isset($_GET['the_stream'])) {
			//nothing
			die();
} else {
		$the_link=trim($_GET['the_stream']);
		if (strpos($the_link,'radionomy.com')) { //is a radionomy.com link
					$songtitle="The song title is not available";
		} else {	
					preg_match ( '#^http://(.*):(.*)/|;#' , $the_link,  $matches);
					$ip=$matches[1];
					$port=$matches[2];
					
					//echo $ip.'  ---  '.$port.'  ---  ';
					
		
					$fp = @fsockopen($ip,$port,$errno,$errstr,1);
					///////@stream_set_blocking($fp, 0);
					if (!$fp) { 
						$songtitle="The song title is not available - Connection refused"; // sever is offline
					} else { 
					
						@fputs($fp, "GET /index.html HTTP/1.0\r\nUser-Agent: Mozilla\r\n\r\n");
						while (!@feof($fp)) {
							$info = @fgets($fp);
						}
						if (stripos($info,'<title>Shoutcast') || stripos($info,'<title>Redirect</title>')) { //Is shoutcas ?
						    $fp = @fsockopen($ip,$port,$errno,$errstr,1);
							@fputs($fp, "GET /7.html HTTP/1.0\r\nUser-Agent: Mozilla\r\n\r\n");
							while (!@feof($fp)) {
								$info = @fgets($fp);
							}
							if (trim($info)) { 
								$info = str_replace('</body></html>', "", $info);
								$split = explode(',', $info);
								if (empty($split[6]) ) {
									$songtitle="The song title is not available"; // sever is online but no song title
								} else {
									$title = str_replace('\'', '`', $split[6]);
									$title = str_replace(',', ' ', $title);
									$songtitle="$title"; // Diaplays song
								}
							}							
						} else { // Then is icecast
								$the_server='http://'.$ip.':'.$port;
								$the_stats_file="/status.xsl";
								//get statistics file contents
								$fp = @fopen($the_server.$the_stats_file,'r');

								if(!$fp) {
								   //error connecting to server!
								  $songtitle = "The song title is not available - Unable to connect to Icecast server.";
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
										if (count($temp)>=1) {
											$radio_info['title'] = $temp[0];
										}
										if (count($temp)>=2) {
											$radio_info['description'] = $temp[1];
										}
										if (count($temp)>=3) {
											$radio_info['content_type'] = $temp[2];
										}
										if (count($temp)>=4) {
											$radio_info['mount_start'] = $temp[3];
										}
										if (count($temp)>=5) {
											$radio_info['bit_rate'] = $temp[4];
										}
										if (count($temp)>=6) {
											$radio_info['listeners'] = $temp[5];
										}
										if (count($temp)>=7) {
											$radio_info['most_listeners'] = $temp[6];
										}
										if (count($temp)>=8) {
											$radio_info['genre'] = $temp[7];
										}
										if (count($temp)>=9) {
											$radio_info['url'] = $temp[8];
										}
										
										//format now playing
										/*$now_playing = explode(" - ",$temp[9]);
										$radio_info['now_playing']['artist'] = $now_playing[0];
										$radio_info['now_playing']['track'] = $now_playing[1];*/
										if (count($temp)>=10) {
											$songtitle=$temp[9];
										}
										//icecast end	
										//GENERAL ICECAST TYPE END < 2.4.0
										
									  if (trim($songtitle)!='') {
										  //nothing
									  } else { //ICECAST TYPE from v2.4.0 and up
										/*$split=explode('</td><td',$stats_file_contents);
										//print_r($split);
										if (count($split)>=11) {
											preg_match('/>(.*)</', $split[10], $matches);
											$songtitle=strip_tags( substr($matches[0],1,strlen($matches[0])) );
										}*/
										
										
										$the_stats_file="/status-json.xsl";
										//get statistics file contents
										$fp = @fopen($the_server.$the_stats_file,'r');
		
										if(!$fp) {
										   //error connecting to server!
										  $songtitle = "The song title is not available - Unable to connect to Icecast server.";
										} else {
										
												$stats_file_contents = '';
												
												while(!@feof($fp)) {
												   $stats_file_contents .= @fread($fp,1024);
												}
												
												@fclose($fp);
												$search_start_point=stripos($stats_file_contents,',"title"');
												if ($search_start_point>0) {
													$stats_file_contents=substr ( $stats_file_contents,$search_start_point );
													$search_end_point=stripos($stats_file_contents,'","dummy');
													$stats_file_contents=substr ( $stats_file_contents,10, $search_end_point-10);
													$songtitle = $stats_file_contents;
												}
										}
									  }										
								}
													
						
						}
					}
						
					/*echo $songtitle;
					if (file_exists('http://'.$ip.':'.$port.'/7.html')) {
						echo "The file 7.html exists";
					} else {
						echo 'The file 7.html does not exist'.'http://'.$ip.':'.$port.'/7.html';
					}*/
		}
		
		if (trim($songtitle)=='') {
				$songtitle="The song title is not available";
		}
		
		//echo strip_tags($songtitle);
		echo strip_tags($songtitle);
}
?>
