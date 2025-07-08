<?php
// thai-setdata_api.php
header('Content-Type: application/json; charset=utf-8');

/**
 * SET (Stock Exchange of Thailand) Data Scraper using DOMDocument/XPath
 * MainValue = ထိပ် 4 တစ်လုံး, နောက်ပိတ် 4 တစ်လုံး (SET နောက်ဆုံး 4, Value ၅လုံး​မြောက် ဒဿမရှေ့ 4)
 * Show update datetime from SET site.
 */

class SETDataScraper {
    private $baseUrl = 'https://www.set.or.th';
    private $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
    private $cookieJar;

    public function __construct() {
        $this->cookieJar = sys_get_temp_dir() . '/set_dom_cookies.txt';
        if (!file_exists($this->cookieJar)) {
            touch($this->cookieJar);
        }
    }

    private function fetchData($url) {
        $ch = curl_init();
        $headers = [
            'User-Agent: ' . $this->userAgent,
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        ];
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_COOKIEJAR => $this->cookieJar,
            CURLOPT_COOKIEFILE => $this->cookieJar,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        // Ensure UTF-8 for Thai text
        if ($response && stripos($response, '<meta charset=') === false) {
            $response = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />' . $response;
        }
        return $response;
    }

    public function getSETLastAndValueAndUpdate() {
        $html = $this->fetchData($this->baseUrl . '/th/home');
        if (!$html) return null;
        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        // SET & Value
        $row = $xpath->query("//tr[td[1][translate(normalize-space(), 'set', 'SET')='SET']]");
        $set = $value = $updateDate = null;
        if ($row->length > 0) {
            $cols = $row[0]->getElementsByTagName("td");
            if ($cols->length >= 5) {
                $set = trim($cols->item(1)->textContent);
                $value = trim($cols->item(4)->textContent);
            }
        }
        // Try to find update datetime
        $nodes = $xpath->query("//*[contains(text(),'ข้อมูลล่าสุด') or contains(text(),'Last Update') or contains(text(),'Last updated')]");
        if ($nodes->length > 0) {
            $updateText = trim($nodes[0]->textContent);
            if (preg_match('/(\d{1,2} [ก-๙]+\.? \d{4} \d{2}:\d{2}:\d{2})/', $updateText, $m)) {
                $updateDate = $m[1]; // Thai date
            } elseif (preg_match('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $updateText, $m)) {
                $updateDate = $m[1];
            }
        }
        return [
            'SET' => $set,
            'Value' => $value,
            'Update' => $updateDate,
        ];
    }
}

// Main logic
$scraper = new SETDataScraper();
$data = $scraper->getSETLastAndValueAndUpdate();

if ($data && $data['SET'] && $data['Value']) {
    // MainValue logic
    $setNoComma = str_replace(',', '', $data['SET']);
    $setNoDot = str_replace('.', '', $setNoComma);
    $setLastDigit = substr($setNoDot, -1);

    $valueNoComma = str_replace(',', '', $data['Value']);
    $dotPos = strpos($valueNoComma, '.');
    $valueNoDecimal = $dotPos !== false ? substr($valueNoComma, 0, $dotPos) : $valueNoComma;
    $valueFifthDigit = (strlen($valueNoDecimal) >= 5) ? substr($valueNoDecimal, 4, 1) : '';

    $mainValue = $setLastDigit . $valueFifthDigit;

    echo json_encode([
        "MainValue" => $mainValue,
        "Set" => $data['SET'],
        "Value" => $data['Value'],
        "Update" => $data['Update'] ?? date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        "MainValue" => "-",
        "Set" => "-",
        "Value" => "-",
        "Update" => "-"
    ], JSON_UNESCAPED_UNICODE);
}
?>