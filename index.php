<?php

/*
Copyright (c) 2009 Brandon Williams

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

// DNS Benchmark Analysis

require('config.php');

//Connect to database
$dbConn = new PDO("mysql:host={$db_host};dbname={$db_name}", "{$db_user}", "{$db_pass}");
$dbConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$success = false;

//First off, do we have a form submit?
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['go'] == 'go!') {
    //TODO: does form pass CSRF?
    
    //Did they give us an email?
    if ($_POST['email'] != '') {
    
        //Did the user upload a CSV?
        if ($_FILES['csv']['error'] === UPLOAD_ERR_OK) {
            require 'csv.php';
            
            $t1 = microtime(true);
            $parse = resultsFromCsv($_FILES['csv']['tmp_name'], $_POST['email'], $dbConn);
            echo 'Execution Time: ';
            echo microtime(true) - $t1;
            
            if ($parse !== true) {
                $err = $parse;
            } else {
                $success = true;
            }
            
            
        //Was there any data present?
        } else if ($_POST['results'] != '') {
    
            //Let's sanitize shall we?
            $rawResults = htmlentities(trim($_POST['results']) ,ENT_NOQUOTES);
    
            //Did we recieve benchmark data?
            //Two ways to check:
            //1) Simple string search
            if (strpos($rawResults, 'Interpreting your benchmark results above:') !== false) {
            
                //2) Check for a data grid - TODO
            
                //Get rid of "help" data
                $results = substr($rawResults, strpos($rawResults, 'fastest to slowest)')+19);
                $results = substr($results, 0, strpos($results, 'Interpreting your benchmark results above:'));
                $results = trim($results);
                
                //Get the date this benchmark was taken
                $resultsDate = array();
                if (preg_match('/UTC: (\d{4}-\d{2}-\d{2}), from (\d{2}:\d{2}:\d{2}) to (\d{2}:\d{2}:\d{2}), for (\d{2}:\d+\.\d+)/', $results, $resultsDate)) {
                    $benchmarkDate = $resultsDate[1] . ' ' . $resultsDate[2];
                    
                    //Has this benchmark been inserted already?
                    $query = 'SELECT count(id) FROM benchmarks WHERE date = ? and user = ?';
                    $select = $dbConn->prepare($query);
                    $select->execute(array($benchmarkDate, $_POST['email']));
                    $result = $select->fetch(PDO::FETCH_NUM);
                    
                    if ($result[0] == 0) {
                                
                        //Create a new result set
                        $query = "INSERT INTO benchmarks VALUES (null, ?, ?, ?);";
                        $insert = $dbConn->prepare($query);
                        $insert->execute(array($benchmarkDate, $resultsDate[4], $_POST['email']));
                        $benchmarkId = $dbConn->lastInsertId();
                        //echo $benchmarkId . '|' . print_r($resultsDate, true) . '<br />';
                    
                        //It looks like we can procede with getting results for each DNS server
                        $ipRegex = '(?:(?:[\d\s]){2}\d\.){3}(?:[\d\s]){2}\d';
                        //Padd $results in case the first IP doesn't start with 3 numbers (ex 67. 19. 1. 10) which would make it not get matched
                        $results = '  ' . $results;
                        $rawDnsServers = array();
                        if (preg_match_all('/(' . $ipRegex . '.*?)(?=' . $ipRegex . '|UTC)/s', $results, $rawDnsServers)) {
                            $dnsServers = $rawDnsServers[1];
                            //$dnsServers = array();
                            
                            $t1 = microtime(true);
                            
                            $dbConn->beginTransaction();
                            
                            $query = "INSERT INTO results (benchmark, server, type, min, avg, max, `std.dev`, reliab) VALUES (:benchmark, :server, :type, :min, :avg, :max, :stdDev, :reliab)";
                            $resultInsert = $dbConn->prepare($query);
                            $resultInsert->bindParam(':benchmark', $benchmark);
                            $resultInsert->bindParam(':server', $server);
                            $resultInsert->bindParam(':type', $type);
                            $resultInsert->bindParam(':min', $min);
                            $resultInsert->bindParam(':avg', $avg);
                            $resultInsert->bindParam(':max', $max);
                            $resultInsert->bindParam(':stdDev', $stdDev);
                            $resultInsert->bindParam(':reliab', $reliab);
                            
                            
                            foreach ($dnsServers as $dnsServer) {
                            
                                //Get the IP address
                                preg_match('/' . $ipRegex . '/', $dnsServer, $ip);
                                $ip = $ip[0];
                                //echo print_r($ip, true) . '<br />';
                                //Get the reverse DNS lookup and Owner
                                preg_match('/\s*(.*?)\s*\n\s*(.*?)$/', trim($dnsServer), $meta);
                                //echo '<pre>' . print_r($meta, true) . '</pre><br />';
                                
                                try {
                                    $query = "INSERT INTO servers VALUES (?, ?, ?)";
                                    $statement = $dbConn->prepare($query);
                                    $statement->execute(array($ip, $meta[1], $meta[2]));
                                } catch (PDOException $e) {
                                    //Ignore this error!
                                }
                                
                                //Does this server work?
                                if (strpos($dnsServer, '| The') === false && strpos($dnsServer, '| DNS') === false) {
                                
                                    $benchmark = $benchmarkId;
                                    $server = $ip;
                                    
                                    $resultRegex = '\| +(\d+\.\d+) ';
                                    //Get the cached results
                                    preg_match('/Cached Name\s+' . $resultRegex . $resultRegex . $resultRegex . $resultRegex . $resultRegex . '/', $dnsServer, $cached);
                                    //echo print_r($cached, true) . '<br />';
                                    $type = 'cache';
                                    $min = $cached[1];
                                    $avg = $cached[2];
                                    $max = $cached[3];
                                    $stdDev = $cached[4];
                                    $reliab = $cached[5];
                                    $resultInsert->execute();
                                    
                                    //Get the uncached results
                                    preg_match('/Uncached Name\s+' . $resultRegex . $resultRegex . $resultRegex . $resultRegex . $resultRegex . '/', $dnsServer, $uncached);
                                    //echo print_r($uncached, true) . '<br />';
                                    $type = 'uncache';
                                    $min = $uncached[1];
                                    $avg = $uncached[2];
                                    $max = $uncached[3];
                                    $stdDev = $uncached[4];
                                    $reliab = $uncached[5];
                                    $resultInsert->execute();
                                    
                                    //Get the dotcom results
                                    preg_match('/DotCom Lookup\s+' . $resultRegex . $resultRegex . $resultRegex . $resultRegex . $resultRegex . '/', $dnsServer, $dotCom);
                                    //echo print_r($dotCom, true) . '<br />';
                                    $type = 'DotCom';
                                    $min = $dotCom[1];
                                    $avg = $dotCom[2];
                                    $max = $dotCom[3];
                                    $stdDev = $dotCom[4];
                                    $reliab = $dotCom[5];
                                    $resultInsert->execute();
                                    
                                }
                                
                                $success = true;

                            }
                            
                            $dbConn->commit();
                            
                            echo 'Execution Time: ';
                            echo microtime(true) - $t1;
                        
                        } else {
                            $err = "Could not find any nameserver results, please try again.";
                        }
                    } else {
                        $err = "You've already entered that result set.";
                    }
                } else {
                    $err = "Could not determine the date of benchmark, please try again.";
                }
            } else {
                $err = "It looks like you didn't input benchmark results, please try again.";
            }
        } else {
            $err = 'You forgot to paste the results or upload a CSV!';
        }
    } else {
        $err = 'Please enter your email.';
    }
}

require 'header.php';
?>
        <p class="info">Already have imported result sets? Check them out on the <a href="results.php">results page</a>!
        <?php
        if ($err != '') {
            echo '<p class="error clear">' . $err . '</p>';
        } else if($success) {
            echo '<p class="success clear">Data Imported!<br />See your <a href="results.php?user=' . urlencode($_POST['email']) . '">results</a></p>';
        }
        ?>
        <form action="" method="post" enctype="multipart/form-data" onsubmit="disableForm();" class="clear">
        <table border="0" cellpadding="5" cellspacing="0">
            <tr>
                <td valign="top">
                    Paste results below:<br />
                    <a href="images/help1.png"><img src="images/help1.png" width="500" /></a><br />
                    <textarea name="results" id="results" cols="60" rows="12" tabindex="1"></textarea><br />
                </td>
                <td valign="top"><strong>OR</strong></td>
                <td valign="top">
                    Upload results CSV:<br />
                    <input type="file" name="csv" id="csv" tabindex="3" /><br />
                    <a href="images/help2.png"><img src="images/help2.png" width="500" /></a>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <input type="hidden" name="go" id="go" value="go!" />
                    Your Email: <input type="text" name="email" id="email" value="<?php echo htmlentities($_POST['email']) ?>" tabindex="2" /><br />
                    <input type="submit" name="submit" id="submit" value="Submit" tabindex="4" />
                </td>
            </tr>
        </table>
        </form>
<?php require 'footer.php'; ?>