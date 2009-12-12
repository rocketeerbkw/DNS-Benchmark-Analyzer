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

function resultsFromCsv($fileLoc, $user, $dbConn) {

    $file = fopen($fileLoc, 'r');

    $benchmarkId = 0;
    $linesProcessed = 0;

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

    while (($data = fgetcsv($file)) !== false) {
    
        //Skip the headers row and non-array rows (someone didn't upload proper csv)
        if ($data[0] == "Server IP" || count($data) != 25) {
            continue;
        }
        
        $linesProcessed++;
        
        //Benchark Meta
        if ($benchmarkId === 0) {
            $date = $data[23];
            $duration = $data[24];
            
            //Has this benchmark been inserted already?
            $query = 'SELECT count(id) FROM benchmarks WHERE date = ? and user = ?';
            $select = $dbConn->prepare($query);
            $select->execute(array($date, $user));
            $result = $select->fetch(PDO::FETCH_NUM);
            
            if ($result[0] > 0) {
                $dbConn->rollBack();
                return 'You\'ve already entered that result set.';
            }
            
            //Create a new result set
            $query = "INSERT INTO benchmarks VALUES (null, ?, ?, ?);";
            $insert = $dbConn->prepare($query);
            $insert->execute(array($date, $duration, $user));
            $benchmarkId = $dbConn->lastInsertId();
        }
        
        //Server Meta
        $ip = trim($data[0]);
        //Make the IP match the format that comes from regex parsing results
        //Fixed length string (16), each section left padded with space
        $ip = preg_replace('/\s*([\d\s][\d\s][\d\s](?:\.|$))/', '$1', '   '.$ip);
        $owner = $data[22];
        $reverse = $data[21];
        $status = $data[2];
        
        //IP is has a unique constraint, so if insert fails, just ignore it
        try {
            $query = "INSERT INTO servers VALUES (?, ?, ?, ?)";
            $statement = $dbConn->prepare($query);
            $statement->execute(array($ip, $reverse, $owner, $status));
        } catch (PDOException $e) {}
        
        //If server is broken, no need to get/save any other data
        if ($status != "Offline" && $status != "No Error Reply" && $status != "Refuses") {
            
            $benchmark = $benchmarkId;
            $server = $ip;

            //Cached Data
            $type = 'cache';
            $min = $data[3];
            $avg = $data[4];
            $max = $data[5];
            $stdDev = $data[6];
            $reliab = number_format(($data[8] / $data[7] * 100), 2);
            $resultInsert->execute();
            
            //Uncached Data
            $type = 'uncache';
            $min = $data[9];
            $avg = $data[10];
            $max = $data[11];
            $stdDev = $data[12];
            $reliab = number_format(($data[14] / $data[13] * 100), 2);
            $resultInsert->execute();
            
            //DotCom Data
            $type = 'DotCom';
            $min = $data[15];
            $avg = $data[16];
            $max = $data[17];
            $stdDev = $data[18];
            $reliab = number_format(($data[20] / $data[19] * 100), 2);
            $resultInsert->execute();

        }
    }
    
    $dbConn->commit();
    
    fclose($file);
    
    if ($linesProcessed === 0) {
        return "That was not a valid CSV";
    }
    
    return true;
}

?>