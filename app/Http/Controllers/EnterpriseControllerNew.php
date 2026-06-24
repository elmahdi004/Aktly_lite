<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;

class EnterpriseControllerNew extends Controller
{
    // The secret API key (should be stored securely in .env in production)
    private const SECRET_API_KEY = 'G75tSbiroK0gda3tgeGOtLZ03O';

    public function read(Request $request)
    {
        // API Key check
        // $apiKey = $request->header('API_KEY');
        // if ($apiKey !== self::SECRET_API_KEY) {
        //     return response('Unauthorized', 401);
        // }

        // Validate required parameters
        // $validator = Validator::make($request->all(), [
        //     'langue' => 'required|string',
        //     'entrepriseNumber' => 'required|integer',
        // ]);
        // if ($validator->fails()) {
        //     return response('Missing required parameters: langue and entrepriseNumber.', 400);
        // }

        $langue = $request->input('langue', $request->input('lang', 'fr'));
        $entrepriseNumber = $request->input('enterprise_number');
        // Ensure we have valid parameters
        if (empty($entrepriseNumber)) {
            return response('Missing required parameter: entrepriseNumber', 400);
        }
        if (empty($langue)) {
            $langue = 'fr'; // Default language
        }

        try {
            $username = env('KBO_WS_USERNAME', 'wsop4830');
            $password = env('KBO_WS_PASSWORD', 'cBRABbE6qmvvFWBEnc6RJJVd');

            // KBO Public Web Service endpoint — version R018.00.
            // (The previous kbopubws110000 path now returns HTTP 404.)
            $serviceUrl = env('KBO_WS_URL', 'https://kbopub.economie.fgov.be/kbopubws180000/services/wsKBOPub');

            // Generate WS-Security elements dynamically for every request
            $nonceBytes = random_bytes(16);
            $nonce = base64_encode($nonceBytes);
            $created = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.v\Z');
            $expires = (new \DateTime('now', new \DateTimeZone('UTC')))
                ->add(new \DateInterval('PT5M'))
                ->format('Y-m-d\TH:i:s.v\Z');
            $passwordDigest = base64_encode(sha1($nonceBytes . $created . $password, true));
            $usernameTokenId = 'UsernameToken-' . strtoupper(bin2hex(random_bytes(16)));
            $timestampId = 'TS-' . strtoupper(bin2hex(random_bytes(16)));

            // Build the SOAP request with mustUnderstand="1"
            $soapRequest = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:dat="http://economie.fgov.be/kbopub/webservices/v1/datamodel" xmlns:mes="http://economie.fgov.be/kbopub/webservices/v1/messages" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
   <soapenv:Header>
      <wsse:Security xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" soapenv:mustUnderstand="1">
         <wsse:UsernameToken wsu:Id="' . $usernameTokenId . '">
            <wsse:Username>' . $username . '</wsse:Username>
            <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordDigest">' . $passwordDigest . '</wsse:Password>
            <wsse:Nonce EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">' . $nonce . '</wsse:Nonce>
            <wsu:Created>' . $created . '</wsu:Created>
         </wsse:UsernameToken>
         <wsu:Timestamp wsu:Id="' . $timestampId . '">
            <wsu:Created>' . $created . '</wsu:Created>
            <wsu:Expires>' . $expires . '</wsu:Expires>
         </wsu:Timestamp>
      </wsse:Security>
      <mes:RequestContext>
         <mes:Id>Test-1</mes:Id>
         <mes:Language>' . $langue . '</mes:Language>
      </mes:RequestContext>
   </soapenv:Header>
   <soapenv:Body>
      <mes:ReadEnterpriseRequest>
         <dat:EnterpriseNumber>' . $entrepriseNumber . '</dat:EnterpriseNumber>
      </mes:ReadEnterpriseRequest>
   </soapenv:Body>
</soapenv:Envelope>';

            // Debug: Log the request before sending
            Log::info('About to make SOAP call with enterprise number: ' . $entrepriseNumber);
            Log::info('SOAP Request to send: ' . $soapRequest);

            // Send the request using cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $serviceUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $soapRequest);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: text/xml;charset=UTF-8',
                'SOAPAction: "http://fgov.economie.be/kbopub/ReadEnterprise"'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Debug: Log the response
            Log::info('HTTP Response Code: ' . $httpCode);
            Log::info('SOAP Response received: ' . $response);
            Log::info('Raw SOAP response: ' . $response);

            if ($response === false) {
                throw new \Exception('cURL request failed');
            }
            $response = trim($response);

            // Force to UTF-8 in case it isn't
            if (function_exists('mb_convert_encoding')) {
                $response = mb_convert_encoding(
                    $response,
                    'UTF-8',
                    mb_detect_encoding($response, 'UTF-8, ISO-8859-1, WINDOWS-1252', true)
                );
            }

libxml_use_internal_errors(true);
$xml = simplexml_load_string($response);

if ($xml === false) {
    Log::error("XML parsing failed.");
    foreach (libxml_get_errors() as $error) {
        Log::error("LibXML Error: " . $error->message);
    }
    libxml_clear_errors();
    return response()->json(['error' => 'Failed to parse XML response.'], 500);
}

// Get namespaces
$namespaces = $xml->getNamespaces(true);

// Access the Body (handle soap or soapenv prefix)
$soapNs = $namespaces['soap'] ?? $namespaces['soapenv'] ?? null;
if (!$soapNs) {
    return response()->json(['error' => 'SOAP namespace not found.'], 500);
}
$body = $xml->children($soapNs)->Body ?? null;
if (!$body) {
    return response()->json(['error' => 'SOAP Body not found.'], 500);
}

// Dive into ReadEnterpriseReply (search any namespace)
$reply = null;
foreach ($namespaces as $ns) {
    if (isset($body->children($ns)->ReadEnterpriseReply)) {
        $reply = $body->children($ns)->ReadEnterpriseReply;
        break;
    }
}
if (!$reply) {
    return response()->json(['error' => 'ReadEnterpriseReply not found.'], 500);
}

// Get the actual Enterprise node (search any namespace)
$enterprise = null;
foreach ($namespaces as $ns) {
    if (isset($reply->children($ns)->Enterprise)) {
        $enterprise = $reply->children($ns)->Enterprise;
        break;
    }
}
if (!$enterprise) {
    return response()->json(['error' => 'Enterprise data not found.'], 500);
}

// Convert to array
$data = json_decode(json_encode($enterprise), true);

// Normalize keys to camelCase
$data = array_map_recursive_keys($data, function ($key) {
    return toCamelCase($key);
});

return response()->json($data, 200);

        } catch (\Exception $e) {
            Log::error('Error in readEnterprise: ' . $e->getMessage());
            return response('Error: ' . $e->getMessage(), 500);
        }
    }
} 





function array_map_recursive_keys(array $array, callable $callback): array
{
    $newArray = [];
    foreach ($array as $key => $value) {
        $newKey = is_string($key) ? $callback($key) : $key;
        if (is_array($value)) {
            $value = array_map_recursive_keys($value, $callback);
        }
        $newArray[$newKey] = $value;
    }
    return $newArray;
}

function toCamelCase($string): string
{
    return lcfirst(str_replace(' ', '', ucwords(str_replace(['_', '-', '.'], ' ', $string))));
}
