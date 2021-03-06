<?php

namespace Detection\MobileDetect\Varnish;

class DeviceDetect
{
    private function getRules()
    {
        $detect = new \Mobile_Detect();

        $utilities = $detect->getUtilities();
        return array(
            'uaMatch' => array(
                'phones'   => $detect->getPhoneDevices(),
                'tablets'  => $detect->getTabletDevices(),
                'os' => $detect->getOperatingSystems(),
                'browsers' => $detect->getBrowsers(),
                'bots' => array(
                    'Bots' => $utilities['Bot'],
                    'MobileBots' => $utilities['MobileBot'],
                ),
            ),
            'version' => $detect->getScriptVersion(),
        );
    }

    public function generateVcl()
    {
        $rules = $this->getRules();

        $vcl = <<<EOT
sub devicedetect {
	#Based on Mobile_Detect {$rules['version']}

	#https://github.com/serbanghita/Mobile-Detect
	unset req.http.X-UA-Device;
	set req.http.X-UA-Device = "desktop";
	# Handle that a cookie may override the detection alltogether.
	if (req.http.Cookie ~ "(?i)X-UA-Device-force") {
		/* ;?? means zero or one ;, non-greedy to match the first. */
		set req.http.X-UA-Device = regsub(req.http.Cookie, "(?i).*X-UA-Device-force=([^;]+);??.*", "\\1");
		/* Clean up our mess in the cookie header */
		set req.http.Cookie = regsuball(req.http.Cookie, "(^|; ) *X-UA-Device-force=[^;]+;? *", "\\1");
		/* If the cookie header is now empty, or just whitespace, unset it. */
		if (req.http.Cookie ~ "^ *$") { unset req.http.Cookie; }
	} else {
EOT;

        $phones = $rules['uaMatch']['phones'];
        $vcl .= $this->returnVarnishRules($phones, "mobile");
        $mobileBrowsers = $rules['uaMatch']['browsers'];
        $vcl .= $this->returnVarnishRules($mobileBrowsers, "mobile", true);
        $mobileOS = $rules['uaMatch']['os'];
        $vcl .= $this->returnVarnishRules($mobileOS, "mobile", true);
        $tablets = $rules['uaMatch']['tablets'];
        $vcl .= $this->returnVarnishRules($tablets, "tablet");
        $bots = $rules['uaMatch']['bots'];
        $vcl .= $this->returnVarnishRules($bots, "bot");

        $vcl .= <<<EOT
	}
}
EOT;

        return $vcl;
    }

    private function returnVarnishRules($rulesArray, $key, $useElse = false){
        $retString = "\t\t";
        if ($useElse){
            $retString .= "elsif (\n";
        } else {
            $retString .= "if (\n";
        }

        $count = 0;
        foreach($rulesArray as $rule){
            $retString .= "\t\t";
            $retString .= "   (req.http.User-Agent ~ \"(?i)$rule\")";
            if ($count < (count((array)$rulesArray) -1)){
                $retString .= " ||\n";
            }else{
                $retString .= ") {\n";
            }
            $count++;
        }

        $retString .= "\t\t\tset req.http.X-UA-Device = \"$key\";\n";
        $retString .= "\t\t}\n\n";

        return $retString;
    }
}