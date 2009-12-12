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

require('config.php');

//Connect to database
$dbConn = new PDO("mysql:host={$db_host};dbname={$db_name}", "{$db_user}", "{$db_pass}");
$dbConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$hoursToDataPoint = array('11pm' => 0,
                         '10pm' => 1,
                         '9pm' => 2,
                         '8pm' => 3,
                         '7pm' => 4,
                         '6pm' => 5,
                         '5pm' => 6,
                         '4pm' => 7,
                         '3pm' => 8,
                         '2pm' => 9,
                         '1pm' => 10,
                         '12pm' => 11,
                         '11am' => 12,
                         '10am' => 13,
                         '9am' => 14,
                         '8am' => 15,
                         '7am' => 16,
                         '6am' => 17,
                         '5am' => 18,
                         '4am' => 19,
                         '3am' => 20,
                         '2am' => 21,
                         '1am' => 22,
                         '12am' => 23);

//See if user is "logged in"
$user = false;
if ((isset($_GET['user']) && $_GET['user'] != '') || (isset($_POST['user']) && $_POST['user'] != '')) {
    $user = isset($_POST['user']) ? $_POST['user'] : $_GET['user'];
}

if ($user) {

    //Get the date information for benchmarks
    $statement = $dbConn->prepare("SELECT id, date from benchmarks where user = ? order by dayofweek(date), date");
    $statement->execute(array($user));
    $benchmarks = $statement->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($benchmarks) > 0) {
    
        $benchmarkIds = array();
        foreach ($benchmarks as $benchmark) {
            $benchmarkIds[] = $benchmark['id'];
        }
        
        $benchmardIds = implode(', ', $benchmarkIds);
        
        //Get benchmark information
        $query = <<<EOS
          select r.server, min(`min`) as `min`, format(avg(`avg`), 3) as `avg`, max(`max`) as `max`, format(avg(reliab), 2) as `reliab`, s.status, s.owner, s.reverse
            from results r
            join servers s on r.server = s.ip
           where type = 'cache'
             and benchmark in ({$benchmardIds})
        group by server
        order by `avg`, `max`
EOS;
        $statement = $dbConn->query($query);
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $err = "You haven't imported any results yet<br />You can do so at the <a href=\"index.php\">import page</a>.";
    }

}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <title>DNS Benchmark Analyzer</title>
        <link rel="stylesheet" href="styles_min.css" />
        
        <script type="text/javascript">
            // Google Analytics
            var _gaq = _gaq || [];
            _gaq.push(['_setAccount', 'UA-2366091-2']);
            _gaq.push(['_trackPageview']);

            (function() {
                var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
                ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
                (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(ga);
            })();
        </script>
    </head>
    <body>
        <h1>DNS Benchmark Analyzer - Results</h1>
        <p>This site is designed to import multiple result sets from <a href="http://www.grc.com/dns/benchmark.htm" target="_blank">DNS Benchmark</a> to give you a better idea of longterm DNS server statistics.</p>
        
        <?php if (!$user): ?>
            <p class="info">Enter your email below to see results.</p>
            <form action="" method="post" class="clear">
                <input type="hidden" name="go" input="go" value="go!" />
                Email: <input type="text" name="user" id="user" value="" />
                <input type="submit" name="submit" id="submit" value="Submit" />
            </form>
        <?php else:
            if ($err != ''):
                echo '<p class="error">' . $err . '</p>';
            else: ?>
                <h3>Showing results for <?php echo htmlentities($user) ?></h3>
                <p>Chronological spread of your benchmarks (all times shown in UTC).</p>
                <ul>
                    <li>Blue dots represent a benchmark taken at that time</li>
                    <li>Est. peak consumer internet traffic for USA shadded in blue.</li>
                </ul>

                <?php
                //Turn the list of dates into x,y coordinates
                $xAxis = array();
                $yAxis = array();
                foreach ($benchmarks as $benchmark) {
                    $day  = date('N', strtotime($benchmark['date']));
                    $hour = $hoursToDataPoint[date('ga', strtotime($benchmark['date']))];
                    $xAxis[] = $day;
                    $yAxis[] = $hour;
                }
                $xAxis = implode(',', $xAxis);
                $yAxis = implode(',', $yAxis);
                $humanHours = implode('|', array_keys($hoursToDataPoint));
                $chartImg = <<<CI
                http://chart.apis.google.com/chart
                ?cht=s
                &chd=t:{$xAxis}|{$yAxis}
                &chds=1,7,0,23
                &chg=101,4.35
                &chm=r,E5ECF9,0,0.0435,0.2175|r,E5ECF9,0,0.783,0.957
                &chxt=x,y
                &chxl=0:|Mon|Tues|Wed|Thurs|Fri|Sat|Sun|1:|{$humanHours}
                &chs=400x400
CI;
        ?>
                
                <img src="<?php echo preg_replace('/\s/s', '', $chartImg) ?>" />
                <p>Servers ordered by fastest cached response time (in milliseconds):<br /><span class="RedirectsALL">These servers redirect to branded search on DNS lookup failure.</span></p>
                <table class="servers" cellpadding="4" cellspacing="0" border="1">
                    <tr>
                        <td>&nbsp;</td>
                        <td>Server</td>
                        <td>Min</td>
                        <td>Avg</td>
                        <td>Max</td>
                        <td>Reliability</td>
                    </tr>
                    <?php for ($server=1;$server<=count($results);$server++):
                        $result = $results[$server-1]; ?>
                        <tr>
                            <td><?php echo $server ?></td>
                            <td align="right" class="<?php echo str_replace(" ", "", htmlentities($result['status'])) ?>"><?php echo str_replace(" ", "&nbsp;", htmlentities($result['server'])) ?></td>
                            <td align="right"><?php echo str_replace(" ", "&nbsp;", htmlentities($result['min'])) ?></td>
                            <td align="right"><?php echo str_replace(" ", "&nbsp;", htmlentities($result['avg'])) ?></td>
                            <td align="right"><?php echo str_replace(" ", "&nbsp;", htmlentities($result['max'])) ?></td>
                            <td align="right">%<?php echo str_replace(" ", "&nbsp;", htmlentities($result['reliab'])) ?></td>
                        </tr>
                    <?php endfor; ?>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </body>
</html>