<?php 

class workingDayCalculator 
{
    protected $holidays = array();

    /**
    * 
    */
    protected function isHoliday($dayAsDateTime, $state = "HH")
    {
        // Retrieve array holding public holidays
        if (empty($this->holidays)) {
            $this->holidays = $this->getPublicHolidays($state);

            if (empty($this->holidays)) {
                $this->holidays = $this->calculatePublicHolidays();
            }
        }

        // Check if public holiday
        if (in_array($dayAsDateTime->format("dm"), $this->holidays)) {
            return true;
        }

        return false;
    }

    /**
    * Get public holidays from rest API service
    *
    * @param $state ISO Code of state, e.g. "HH"
    */
    protected function getPublicHolidays($state)
    {
        // is cURL installed yet?
        if (!function_exists('curl_init')) {
            die('Sorry cURL is not installed!');
        }
     
        // OK cool - then let's create a new cURL resource handle
        $ch = curl_init();
     
        // Now set some options (most are optional)
     
        // Set URL to download
        curl_setopt($ch, CURLOPT_URL, "http://feiertage.jarmedia.de/api/?jahr=" . date("Y"));
     
        // Set a referer
        //curl_setopt($ch, CURLOPT_REFERER, );
     
        // User agent
        curl_setopt($ch, CURLOPT_USERAGENT, "MozillaXYZ/1.0");
     
        // Include header in result? (0 = yes, 1 = no)
        curl_setopt($ch, CURLOPT_HEADER, 0);
     
        // Should cURL return or print out the data? (true = return, false = print)
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
     
        // Timeout in seconds
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
     
        // Download the given URL, and return output
        $output = curl_exec($ch);
     
        // Close the cURL resource, and free system resources
        curl_close($ch);
     
        $result = json_decode($output, true);

        if (!isset($result[$state])) {
            die("State " . $state . " not in list.");
        } else {
            $holidays = array();
            foreach ($result[$state] as $holiday) {
                $holidays[] = date("dm", strtotime($holiday['datum']));
            }
            return $holidays;
        }
    }

    /*
    *   Static calculation of main german public holidays
    *   serves as fallback if API is not available
    */
    protected function calculatePublicHolidays()
    {
        $holidays = array(
            "0101", //Neujahr
            "0105", //Tag der Arbeit
            "0310", //Tag der Deutschen Einheit
            "2512", //Erster Weihnachtstag
            "2612", //Zweiter Weihnachtstag
        );

        $easterTime = easter_date($dayAsDateTime->format("Y"));
        $easter = new \DateTime();
        $easter->setTimestamp($easterTime);

        $holidays[] = $easter->setTimestamp($easterTime - ( 2 * 86400))->format("dm"); // Karfreitag
        $holidays[] = $easter->setTimestamp($easterTime + ( 1 * 86400))->format("dm"); // Ostermontag
        $holidays[] = $easter->setTimestamp($easterTime + (39 * 86400))->format("dm"); // Himmelfahrt
        $holidays[] = $easter->setTimestamp($easterTime + (50 * 86400))->format("dm"); // Pfingstmontag

        return $holidays;
    }

    /*
    *   Returns the working day 24 hours before the day provided
    *
    *   @param $dayAsDateTime dateTime
    */
    public function getWorkday24HoursBeforeDate($dayAsDateTime)
    {
        $aDayAgo = $dayAsDateTime->modify("-1 day");

        while ($this->isHoliday($aDayAgo) || $aDayAgo->format("N") == 6 ||  $aDayAgo->format("N") == 7) {
            $aDayAgo->modify("-1 day");
        }

        return $aDayAgo;
    }
}

$date = isset($_GET['initialTime']) ? $_GET['initialTime'] : date("Y-m-d");

$initialDate = new \DateTime();
$dateToCheck = new \DateTime();

$dateToCheck->setTimestamp(strtotime($date));
$initialDate->setTimestamp(strtotime($date));

$workingDayCalculator = new workingDayCalculator();
$workingDay = $workingDayCalculator->getWorkday24HoursBeforeDate($dateToCheck);

$output = "The last workingDay 24 hours before ". $initialDate->format("d-m-Y") . " is " . $workingDay->format("d-m-Y");

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Calculation of last working day before the day provided</title>
</head>

<body>
    <form method="GET">
        <input type="date" name="initialTime" value="<?php echo $date; ?>">
        <input type="submit" value="Calculate">
    </form>
    <p>
        <?php echo $output; ?>
    </p>
</body>
</html>