<template>
    <div class="container mx-auto p-4">
      <h1 class="text-2xl font-bold my-4">Éditeur de données d'entreprise</h1>
      
      <!-- Recherche d'entreprise -->
      <!-- <div class="mb-6 p-4 bg-gray-100 rounded-lg">
        <div class="flex items-center gap-4">
          <input
            v-model="searchNumber"
            type="text"
            placeholder="Numéro d'entreprise"
            class="p-2 border rounded flex-grow"
          />
          <button @click="fetchData" class="bg-blue-500 text-white px-4 py-2 rounded">
            Rechercher
          </button>
        </div>
      </div> -->
  
      <div v-if="loading" class="text-center py-8">
        <p class="text-lg">Chargement des données...</p>
      </div>
  
      <div v-else-if="data">
        <!-- Informations générales -->
        <div class="mb-6 p-4 bg-white border rounded-lg shadow-sm">
          <h2 class="text-xl font-semibold mb-4">Informations générales</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="form-group">
              <label class="block text-sm font-medium mb-1">Numéro d'entreprise</label>
              <input 
                v-model="data.number" 
                type="text" 
                class="w-full p-2 border rounded"
              />
            </div>
            <div class="form-group">
              <label class="block text-sm font-medium mb-1">Type d'entreprise</label>
              <input 
                v-model="data.typeOfEnterprise" 
                type="text" 
                class="w-full p-2 border rounded"
              />
            </div>
          </div>
        </div>
  
        <!-- Situation juridique -->
        <div class="mb-6 p-4 bg-white border rounded-lg shadow-sm">
          <h2 class="text-xl font-semibold mb-4">Situation juridique</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="form-group">
              <label class="block text-sm font-medium mb-1">Code</label>
              <input 
                v-model="data.juridicalSituation.code" 
                type="text" 
                class="w-full p-2 border rounded"
              />
            </div>
            <div class="form-group">
              <label class="block text-sm font-medium mb-1">Description</label>
              <input 
                v-model="data.juridicalSituation.description[0].value" 
                type="text" 
                class="w-full p-2 border rounded"
              />
            </div>
            <div class="form-group">
              <label class="block text-sm font-medium mb-1">Date de début</label>
              <input 
                v-model="dateBegin" 
                type="date" 
                class="w-full p-2 border rounded"
                @input="updateJuridicalBeginDate"
              />
            </div>
            <div class="form-group">
              <label class="block text-sm font-medium mb-1">Statut</label>
              <input 
                v-model="data.juridicalSituation.status.code" 
                type="text" 
                class="w-full p-2 border rounded"
              />
            </div>
          </div>
        </div>
  
        <!-- Dénomination -->
        <div class="mb-6 p-4 bg-white border rounded-lg shadow-sm">
          <h2 class="text-xl font-semibold mb-4">Dénomination</h2>
          <div v-if="data.denomination && data.denomination.length > 0">
            <div class="form-group mb-4">
              <label class="block text-sm font-medium mb-1">Nom</label>
              <input 
                v-model="data.denomination[0].description[0].value" 
                type="text" 
                class="w-full p-2 border rounded"
              />
            </div>
          </div>
        </div>
  
        <!-- Adresse -->
        <div class="mb-6 p-4 bg-white border rounded-lg shadow-sm">
          <h2 class="text-xl font-semibold mb-4">Adresse</h2>
          <div v-if="data.address && data.address.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="form-group">
              <label class="block text-sm font-medium mb-1">Rue</label>
              <input 
                v-model="data.address[0].street.description[0].value" 
                type="text" 
                class="w-full p-2 border rounded"
              />
            </div>
            <div class="form-group">
              <label class="block text-sm font-medium mb-1">Numéro</label>
              <input 
                v-model="data.address[0].houseNumber" 
                type="text" 
                class="w-full p-2 border rounded"
              />
            </div>
            <div class="form-group">
              <label class="block text-sm font-medium mb-1">Boîte</label>
              <input 
                v-model="data.address[0].box" 
                type="text" 
                class="w-full p-2 border rounded"
              />
            </div>
            <div class="form-group">
              <label class="block text-sm font-medium mb-1">Code postal</label>
              <input 
                v-model="data.address[0].zipcode" 
                type="text" 
                class="w-full p-2 border rounded"
              />
            </div>
            <div class="form-group">
              <label class="block text-sm font-medium mb-1">Commune</label>
              <input 
                v-model="data.address[0].municipality.description[0].value" 
                type="text" 
                class="w-full p-2 border rounded"
              />
            </div>
            <div class="form-group">
              <label class="block text-sm font-medium mb-1">Pays</label>
              <input 
                v-model="data.address[0].country" 
                type="text" 
                class="w-full p-2 border rounded"
              />
            </div>
          </div>
        </div>
  
        <!-- Activités -->
        <div class="mb-6 p-4 bg-white border rounded-lg shadow-sm">
          <h2 class="text-xl font-semibold mb-4">Activités</h2>
          <div v-for="(activity, index) in data.activity" :key="index" class="mb-4 p-3 border rounded">
            <div class="form-group mb-2">
              <label class="block text-sm font-medium mb-1">Code</label>
              <input 
                v-model="activity.code" 
                type="text" 
                class="w-full p-2 border rounded"
              />
            </div>
            <div class="form-group mb-2">
              <label class="block text-sm font-medium mb-1">Description</label>
              <input 
                v-model="activity.description[0].value" 
                type="text" 
                class="w-full p-2 border rounded"
              />
            </div>
            <div class="form-group">
              <label class="block text-sm font-medium mb-1">Classification</label>
              <input 
                v-model="activity.activityClassification.description[0].value" 
                type="text" 
                class="w-full p-2 border rounded"
              />
            </div>
          </div>
        </div>
  
        <!-- Fonctions -->
        <div class="mb-6 p-4 bg-white border rounded-lg shadow-sm">
          <h2 class="text-xl font-semibold mb-4">Fonctions</h2>
          <div v-for="(func, index) in data.function" :key="index" class="mb-4 p-3 border rounded">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="form-group">
                <label class="block text-sm font-medium mb-1">Fonction</label>
                <input 
                  v-model="func.description[0].value" 
                  type="text" 
                  class="w-full p-2 border rounded"
                />
              </div>
              <div class="form-group">
                <label class="block text-sm font-medium mb-1">Nom</label>
                <input 
                  v-model="func.person.surname" 
                  type="text" 
                  class="w-full p-2 border rounded"
                />
              </div>
              <div class="form-group">
                <label class="block text-sm font-medium mb-1">Prénom</label>
                <input 
                  v-model="func.person.givenName" 
                  type="text" 
                  class="w-full p-2 border rounded"
                />
              </div>
            </div>
          </div>
        </div>
  
        <!-- Boutons d'action -->
        <div class="flex justify-end gap-4 mt-6">
          <button @click="resetData" class="bg-gray-500 text-white px-4 py-2 rounded">
            Annuler
          </button>
          <button @click="saveData" class="bg-green-500 text-white px-4 py-2 rounded">
            Enregistrer
          </button>
        </div>
      </div>
  
      <div v-else class="text-center py-8">
        <p class="text-lg">Aucune donnée trouvée. Veuillez effectuer une recherche.</p>
      </div>
    </div>
  </template>
  
  <script>
  export default {
    data() {
      return {
        searchNumber: "",
        data: {
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
            "capital": null,
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
          },
        originalData: null,
        loading: false,
        dateBegin: "",
      };
    },
    methods: {
      fetchData() {
        this.loading = true;
        // Simuler une requête API
        setTimeout(() => {
          // Dans un cas réel, vous utiliseriez une requête axios vers votre API
          // axios.get(`/api/enterprises/${this.searchNumber}`)
          //   .then(response => {
          //     this.data = response.data;
          //     this.originalData = JSON.parse(JSON.stringify(response.data));
          //   })
          
          // Pour la démonstration, on utilise les données du document
          this.data = {
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
            "capital": null,
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
          };
          
          this.originalData = JSON.parse(JSON.stringify(this.data));
          
          // Convertir le timestamp en date pour l'affichage
          if (this.data.juridicalSituation.validityPeriod && this.data.juridicalSituation.validityPeriod.begin) {
            const date = new Date(this.data.juridicalSituation.validityPeriod.begin);
            this.dateBegin = date.toISOString().split('T')[0];
          }
          
          this.loading = false;
        }, 500);
      },
      updateJuridicalBeginDate() {
        if (this.dateBegin) {
          const timestamp = new Date(this.dateBegin).getTime();
          this.data.juridicalSituation.validityPeriod.begin = timestamp;
        }
      },
      saveData() {
        // Dans un cas réel, vous utiliseriez une requête PUT ou PATCH vers votre API
        // axios.put(`/api/enterprises/${this.data.number}`, this.data)
        //   .then(response => {
        //     alert('Données sauvegardées avec succès');
        //     this.originalData = JSON.parse(JSON.stringify(this.data));
        //   })
        
        // Pour la démonstration, on simule une sauvegarde réussie
        setTimeout(() => {
          alert('Données sauvegardées avec succès');
          this.originalData = JSON.parse(JSON.stringify(this.data));
        }, 500);
      },
      resetData() {
        if (this.originalData) {
          this.data = JSON.parse(JSON.stringify(this.originalData));
          
          // Réinitialiser également la date
          if (this.data.juridicalSituation.validityPeriod && this.data.juridicalSituation.validityPeriod.begin) {
            const date = new Date(this.data.juridicalSituation.validityPeriod.begin);
            this.dateBegin = date.toISOString().split('T')[0];
          }
        }
      }
    }
  };
  </script>