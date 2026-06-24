<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use App\Services\BnbApiService;
use Illuminate\Support\Facades\Log;

class KboApiCallController extends Controller
{
   private $bnbApi;

    public function __construct()
    {
        $this->bnbApi = new BnbApiService();
    }
   public function callApi(Request $request){
    $request->validate([
        'enterprise_number'=> 'required',
        'langue' => 'required'
    ]);
    $enterpriseNumber = $request->post('enterprise_number');
    $lang = $request->post('langue');
    if($lang == null){
        return 'error';
    }

    $lang = strtolower($lang);
    $request['lang'] = $lang;
    $request['enterprise_number'] = $enterpriseNumber;

    $controller = new EnterpriseControllerNew();
    $data = $controller->read($request)->getOriginalContent();
    \Log::info('response bce: ' . json_encode($data));
// return $data;
    $data['lang_entre'] = $lang;
    return response($data, 200);

    return json_decode('{
    "lang_entre":"'. $lang .'",
    "number": 782372801,
    "juridicalSituation": {
        "code": "000",
        "description": [
            {
                "value": "Situation normale",
                "language": "fr"
            }
        ],
        "validityPeriod": {
            "begin": 1645138800000,
            "end": null
        },
        "status": {
            "code": "AC",
            "description": [
                {
                    "value": "Actif",
                    "language": "fr"
                }
            ]
        },
        "event": null
    },
    "period": {
        "begin": 1645138800000,
        "end": null
    },
    "duration": null,
    "typeOfEnterprise": "ELP",
    "juridicalForm": {
        "code": "610",
        "description": [
            {
                "value": "Société à responsabilité limitée",
                "language": "fr"
            }
        ],
        "validityPeriod": {
            "begin": 1645138800000,
            "end": null
        },
        "juridicalFormCAC": null
    },
    "capital": {
        "amount": 2000,
        "currency": "EUR"
    },
    "primaryDenomination": [],
    "denomination": [
        {
            "code": "001",
            "description": [
                {
                    "value": "TENTACULE",
                    "language": "fr"
                }
            ],
            "validityPeriod": {
                "begin": 1645138800000,
                "end": null
            }
        }
    ],
    "address": [
        {
            "street": {
                "code": "0043",
                "description": [
                    {
                        "value": "Rue de Birmingham",
                        "language": "fr"
                    }
                ]
            },
            "houseNumber": "289",
            "box": 2300,
            "zipcode": "1070",
            "municipality": {
                "code": "21001",
                "description": [
                    {
                        "value": "Anderlecht",
                        "language": "fr"
                    }
                ]
            },
            "country": "France",
            "extraAddressInfo": null,
            "begin": 1703804400000,
            "typeOfAddress": {
                "code": "REGO",
                "description": [
                    {
                        "value": "Adresse du siège social",
                        "language": "fr"
                    }
                ]
            },
            "technicalCreationReason": null,
            "removed": null
        }
    ],
    "contactInformation": [],
    "businessUnits": 1,
    "branch": [],
    "professionalCompetence": [
        {
            "code": "20090",
            "description": [
                {
                    "value": "Connaissances de gestion de base",
                    "language": "fr"
                }
            ],
            "period": {
                "begin": 1645570800000,
                "end": null
            },
            "exemption": null
        }
    ],
    "qualification": [
        {
            "code": "00084",
            "description": [
                {
                    "value": "Assujettie à la TVA",
                    "language": "fr"
                }
            ],
            "period": {
                "begin": 1645743600000,
                "end": null
            }
        },
        {
            "code": "00295",
            "description": [
                {
                    "value": "Entreprise soumise à inscription",
                    "language": "fr"
                }
            ],
            "period": {
                "begin": 1645570800000,
                "end": null
            }
        }
    ],
    "authorization": [
        {
            "code": "50519",
            "description": [
                {
                    "value": "Connaissances de gestion de base",
                    "language": "fr"
                }
            ],
            "period": {
                "begin": 1645570800000,
                "end": null
            }
        }
    ],
    "activity": [
        {
            "code": "70220",
            "activityGroupAdministration": {
                "activityGroup": "001",
                "description": [
                    {
                        "value": "Activités TVA",
                        "language": "fr"
                    }
                ]
            },
            "version": "2008",
            "description": [
                {
                    "value": "Conseil pour les affaires et autres conseils de gestion",
                    "language": "fr"
                }
            ],
            "period": {
                "begin": 1645743600000,
                "end": null
            },
            "activityClassification": {
                "code": "MAIN",
                "description": [
                    {
                        "value": "Activité principale",
                        "language": "fr"
                    }
                ]
            }
        },
        {
            "code": "85592",
            "activityGroupAdministration": {
                "activityGroup": "001",
                "description": [
                    {
                        "value": "Activités TVA",
                        "language": "fr"
                    }
                ]
            },
            "version": "2008",
            "description": [
                {
                    "value": "Formation professionnelle",
                    "language": "fr"
                }
            ],
            "period": {
                "begin": 1645743600000,
                "end": null
            },
            "activityClassification": {
                "code": "SECO",
                "description": [
                    {
                        "value": "Activité secondaire",
                        "language": "fr"
                    }
                ]
            }
        }
    ],
    "function": [
        {
            "code": "10002",
            "description": [
                {
                    "value": "Administrateur",
                    "language": "fr"
                }
            ],
            "period": {
                "begin": 1703804400000,
                "end": null
            },
            "person": {
                "surname": "Bellaziz",
                "givenName": "El Mahdi"
            },
            "enterpriseNumber": null,
            "naturalPersonFounder": null,
            "functionCAC": null
        }
    ],
    "linkedEnterprise": [],
    "financialData": {
        "annualMeetingMonth": 6,
        "fiscalYearEndDay": 31,
        "fiscalYearEndMonth": 12,
        "exceptionalFiscalYear": {
            "begin": 1644966000000,
            "end": 1672441200000
        },
        "validityPeriod": {
            "begin": 1645138800000,
            "end": null
        }
    },
    "replacement": [],
    "exOfficioStrikingOff": []
}', true);


//return $this->callApiSuccessful();

    //if ($response->successful()) {
        // Parse the JSON response
       // $data = $response->json();
       // Session::put('api-data',$data);
        //return $this->callApiSuccessful();
    //} else {
        // Handle error
        //return response()->json(['error' => 'Failed to fetch data'], 500);
    //}
   }
   private function callApiSuccessful(){
    $data = Session::get('api-data');
    if($data['typeOfEnterprise'] != 'ELP'){
        return redirect()->route('error')->with('error','This is not an ELP , please enter a valid ELP number');
    }else if($data['juridicalSituation']['status']['description'][0]['value'] != 'Actif' && $data['juridicalSituation']['status']['description'][0]['value'] != 'Actief'){
        return redirect()->route('error')->with('error','This enterprise is not active');
    }
    return redirect()->route('idanalyzer.upload');
}
  
}




























