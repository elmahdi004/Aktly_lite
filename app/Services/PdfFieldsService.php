<?php

namespace App\Services;

use App\Models\Demande;

class PdfFieldsService
{
    public function getPdfFields(Demande $demande): array
    {
        $fields = $demande->pdf_fields ?? [];
        if (is_string($fields)) {
            $fields = json_decode($fields, true);
        }
        
        return is_array($fields) ? $fields : [];
    }

    public function getBceData(Demande $demande): array
    {
        $data = $demande->bce_data;
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        if (!is_array($data)) {
            return [];
        }
        return $this->normalizeBceData($data);
    }

    public function applyFormulaire1Overrides(array $bceData, Demande $demande): array
    {
        $bceData = $this->normalizeBceData($bceData);
        $fields = $this->getPdfFields($demande);

        $this->applyOverride($bceData, ['number'], $fields['enterprise_number'] ?? null);
        $this->applyOverride($bceData, ['denomination', 'description', 'value'], $fields['denomination'] ?? null);
        $this->applyOverride($bceData, ['juridicalForm', 'description', 'value'], $fields['juridical_form'] ?? null);

        $this->applyOverride($bceData, ['address', 'street', 'description', 'value'], $fields['form1_address_street'] ?? null);
        $this->applyOverride($bceData, ['address', 'houseNumber'], $fields['form1_address_house_number'] ?? null);
        $this->applyOverride($bceData, ['address', 'box'], $fields['form1_address_box'] ?? null);
        $this->applyOverride($bceData, ['address', 'zipcode'], $fields['form1_address_zipcode'] ?? null);
        $this->applyOverride($bceData, ['address', 'municipality', 'description', 'value'], $fields['form1_address_municipality'] ?? null);
        $addressCountry = $fields['form1_address_country'] ?? ($bceData['address']['country'] ?? null);
        if ($this->isMissing($addressCountry)) {
            $addressCountry = $this->getDefaultCountry($demande);
        }
        $this->applyOverride($bceData, ['address', 'country'], $addressCountry);

        // Ensure all address fields are converted to strings
        $bceData = $this->ensureAddressStrings($bceData);

        return $bceData;
    }

    public function applyFormulaire2Overrides(array $bceData, Demande $demande): array
    {
        $bceData = $this->normalizeBceData($bceData);
        $fields = $this->getPdfFields($demande);

        $this->applyOverride($bceData, ['number'], $fields['enterprise_number'] ?? null);
        $this->applyOverride($bceData, ['denomination', 'description', 'value'], $fields['denomination'] ?? null);
        $this->applyOverride($bceData, ['juridicalForm', 'description', 'value'], $fields['juridical_form'] ?? null);

        $this->applyOverride($bceData, ['capital', 'currency'], $fields['capital_currency'] ?? null);
        $this->applyOverride($bceData, ['capital', 'amount'], $fields['capital_amount'] ?? null);

        $this->applyOverride($bceData, ['financialData', 'fiscalYearEndDay'], $fields['fiscal_year_end_day'] ?? null);
        $this->applyOverride($bceData, ['financialData', 'fiscalYearEndMonth'], $fields['fiscal_year_end_month'] ?? null);
        $this->applyOverride($bceData, ['financialData', 'annualMeetingMonth'], $fields['annual_meeting_month'] ?? null);

        return $bceData;
    }

    public function buildAttestation(Demande $demande): array
    {
        $fields = $this->getPdfFields($demande);
        $bceData = $this->getBceData($demande);

        $enterpriseNumber = $fields['enterprise_number'] ?? $this->getBceValue($bceData, 'number');
        $denomination = $fields['denomination'] ?? $this->getBceValue($bceData, 'denomination.description.value');

        $addressStreet = $fields['form1_address_street'] ?? $this->getBceValue($bceData, 'address.street.description.value');
        $addressBox = $fields['form1_address_box'] ?? $this->getBceValue($bceData, 'address.box');
        $addressZipcode = $fields['form1_address_zipcode'] ?? $this->getBceValue($bceData, 'address.zipcode');
        $addressMunicipality = $fields['form1_address_municipality'] ?? $this->getBceValue($bceData, 'address.municipality.description.value');

        $device = $this->getBceValue($bceData, 'capital.currency') ?? 'EUR';
        $bDate = $this->getBceValue($bceData, 'period.begin');
        $exerciceSocial = $this->formatExerciceSocial($bceData);
        $assembleeGenerale = $this->getBceValue($bceData, 'financialData.annualMeetingMonth');

        $defaults = [
            'attestation_company_name' => $denomination,
            'attestation_identifier' => $enterpriseNumber,
            'attestation_device' => $device,
            'attestation_b_date' => $bDate,
            'attestation_exercice_social' => $exerciceSocial,
            'attestation_assemblee_generale' => $assembleeGenerale,
        ];

        $map = [
            'company_name' => 'attestation_company_name',
            'identifier' => 'attestation_identifier',
            'device' => 'attestation_device',
            'b_date' => 'attestation_b_date',
            'exercice_social' => 'attestation_exercice_social',
            'assemblee_generale' => 'attestation_assemblee_generale',
            'c_nom_registre' => 'attestation_c_nom_registre',
            'numero_identification' => 'attestation_numero_identification',
            'site' => 'attestation_site',
            'sigle' => 'attestation_sigle',
            'rue2' => 'attestation_rue2',
            'n2' => 'attestation_n2',
            'boite2' => 'attestation_boite2',
            'code_postal2' => 'attestation_code_postal2',
            'localite2' => 'attestation_localite2',
            'date_de_dissolution' => 'attestation_date_de_dissolution',
            'date_de_cloture' => 'attestation_date_de_cloture',
        ];

        $attestation = [];
        foreach ($map as $attestationKey => $fieldKey) {
            $value = $fields[$fieldKey] ?? ($defaults[$fieldKey] ?? null);
            if ($this->isFilled($value)) {
                $attestation[$attestationKey] = $value;
            }
        }

        return $attestation;
    }

    public function buildFormulaire2Address(Demande $demande): array
    {
        $fields = $this->getPdfFields($demande);
        $addressModel = $demande->address;
        $address = [
            'rue' => $addressModel?->rue ?? null,
            'numero' => $addressModel?->numero ?? null,
            'box' => $addressModel?->box ?? null,
            'zip_code' => $addressModel?->zip_code ?? null,
            'localite' => $addressModel?->localite ?? null,
            'pays' => $addressModel?->pays ?? null,
        ];

        $overrides = [
            'rue' => 'form2_address_rue',
            'numero' => 'form2_address_numero',
            'box' => 'form2_address_box',
            'zip_code' => 'form2_address_zip_code',
            'localite' => 'form2_address_localite',
            'pays' => 'form2_address_pays',
        ];

        foreach ($overrides as $key => $fieldKey) {
            $value = $fields[$fieldKey] ?? null;
            if ($this->isFilled($value)) {
                $address[$key] = $value;
            }
        }

        if ($this->isMissing($address['pays'] ?? null)) {
            $address['pays'] = $this->getDefaultCountry($demande);
        }

        // Ensure all address fields are converted to strings
        $address = $this->ensureFormulaire2AddressStrings($address);

        return $address;
    }

    public function getMissingFields(Demande $demande, ?string $form): array
    {
        $form = strtolower((string) $form);
        if ($form === 'formulaire1') {
            return $this->getMissingFieldsFormulaire1($demande);
        }
        if ($form === 'formulaire2') {
            return $this->getMissingFieldsFormulaire2($demande);
        }
        return [];
    }

    public function getFormFields(Demande $demande, ?string $form): array
    {
        $form = strtolower((string) $form);
        if ($form === 'formulaire1') {
            return $this->getFormulaire1Fields($demande);
        }
        if ($form === 'formulaire2') {
            return $this->getFormulaire2Fields($demande);
        }
        if ($form === 'all') {
            return $this->getAllPdfFields($demande);
        }
        return [];
    }

    private function getAllPdfFields(Demande $demande): array
    {
        $merged = array_merge(
            $this->getFormulaire1Fields($demande),
            $this->getFormulaire2Fields($demande)
        );

        $deduped = [];
        foreach ($merged as $field) {
            $key = $field['key'] ?? null;
            if ($key === null || $key === '') {
                continue;
            }
            if (!isset($deduped[$key])) {
                $deduped[$key] = $field;
                continue;
            }
            $existingValue = $deduped[$key]['value'] ?? null;
            $nextValue = $field['value'] ?? null;
            if ($this->isMissing($existingValue) && $this->isFilled($nextValue)) {
                $deduped[$key] = $field;
            }
        }

        return array_values($deduped);
    }

    public function updatePdfFields(Demande $demande, array $fields): Demande
    {
        $pdfFields = $this->getPdfFields($demande);
        $demandeUpdates = [];

        foreach ($fields as $key => $value) {
            if (is_string($value)) {
                $value = trim($value);
            }
            if (!$this->isFilled($value)) {
                continue;
            }

            if (in_array($key, ['fait_a', 'date_assemblee'], true)) {
                $demandeUpdates[$key] = $value;
                continue;
            }

            if (str_starts_with($key, 'pdf_')) {
                $pdfKey = substr($key, 4);
                if ($pdfKey !== '') {
                    $pdfFields[$pdfKey] = $value;
                }
            }
        }

        foreach ($demandeUpdates as $key => $value) {
            $demande->setAttribute($key, $value);
        }

        if (!empty($pdfFields)) {
            $demande->pdf_fields = $pdfFields;
        }

        $demande->save();

        return $demande;
    }

    private function getMissingFieldsFormulaire1(Demande $demande): array
    {
        $fields = $this->getPdfFields($demande);
        $bceData = $this->getBceData($demande);
        $missing = [];
        $addressServiceEnabled = (bool) $demande->address_service;

        $enterpriseNumber = $fields['enterprise_number'] ?? ($bceData['number'] ?? null);
        if ($this->isMissing($enterpriseNumber)) {
            $missing[] = $this->field('pdf_enterprise_number', 'text', "Numéro d’entreprise", "Ondernemingsnummer");
        }

        $denomination = $fields['denomination'] ?? ($bceData['denomination']['description']['value'] ?? null);
        if ($this->isMissing($denomination)) {
            $missing[] = $this->field('pdf_denomination', 'text', "Dénomination", "Benaming");
        }

        $juridicalForm = $fields['juridical_form'] ?? $this->getBceValue($bceData, 'juridicalForm.description.value');
        if ($this->isMissing($juridicalForm)) {
            $missing[] = $this->field('pdf_juridical_form', 'text', "Forme légale", "Rechtsvorm");
        }

        if ($addressServiceEnabled) {
            $addressStreet = $fields['form1_address_street'] ?? $this->getBceValue($bceData, 'address.street.description.value');
            if ($this->isMissing($addressStreet)) {
                $missing[] = $this->field('pdf_form1_address_street', 'text', "Rue (siège)", "Straat (zetel)");
            }

            $addressHouseNumber = $fields['form1_address_house_number'] ?? $this->getBceValue($bceData, 'address.houseNumber');
            if ($this->isMissing($addressHouseNumber)) {
                $missing[] = $this->field('pdf_form1_address_house_number', 'text', "Numéro (siège)", "Nummer (zetel)");
            }

            $bceAddressBox = $this->getBceValue($bceData, 'address.box');
            $addressBox = $fields['form1_address_box'] ?? $bceAddressBox;
            if ($this->isFilled($bceAddressBox) && $this->isMissing($addressBox)) {
                $missing[] = $this->field('pdf_form1_address_box', 'text', "Boîte (siège)", "Bus (zetel)");
            }

            $addressZipcode = $fields['form1_address_zipcode'] ?? $this->getBceValue($bceData, 'address.zipcode');
            if ($this->isMissing($addressZipcode)) {
                $missing[] = $this->field('pdf_form1_address_zipcode', 'text', "Code postal (siège)", "Postcode (zetel)");
            }

            $addressMunicipality = $fields['form1_address_municipality'] ?? $this->getBceValue($bceData, 'address.municipality.description.value');
            if ($this->isMissing($addressMunicipality)) {
                $missing[] = $this->field('pdf_form1_address_municipality', 'text', "Localité (siège)", "Gemeente (zetel)");
            }

            $addressCountry = $fields['form1_address_country'] ?? $this->getBceValue($bceData, 'address.country');
            if ($this->isMissing($addressCountry)) {
                $addressCountry = $this->getDefaultCountry($demande);
            }
            if ($this->isMissing($addressCountry)) {
                $missing[] = $this->field('pdf_form1_address_country', 'text', "Pays (siège)", "Land (zetel)");
            }
        }

        $capitalCurrency = $fields['attestation_device'] ?? $this->getBceValue($bceData, 'capital.currency') ?? 'EUR';
        if ($this->isMissing($capitalCurrency)) {
            $missing[] = $this->field('pdf_attestation_device', 'text', "Devise", "Valuta");
        }

        $constitutiveDate = $fields['attestation_b_date'] ?? $this->getBceValue($bceData, 'period.begin');
        if ($this->isMissing($constitutiveDate)) {
            $missing[] = $this->field('pdf_attestation_b_date', 'date', "Date de l’acte constitutif", "Datum van de oprichtingsakte");
        }

        $exerciceSocial = $fields['attestation_exercice_social'] ?? $this->formatExerciceSocial($bceData);
        if ($this->isMissing($exerciceSocial)) {
            $missing[] = $this->field('pdf_attestation_exercice_social', 'text', "Exercice social (fin)", "Boekjaar (einde)");
        }

        $assembleeGenerale = $fields['attestation_assemblee_generale'] ?? $this->getBceValue($bceData, 'financialData.annualMeetingMonth');
        if ($this->isMissing($assembleeGenerale)) {
            $missing[] = $this->field('pdf_attestation_assemblee_generale', 'text', "Assemblée générale", "Algemene vergadering");
        }

        if ($this->isMissing($demande->fait_a ?? null)) {
            $missing[] = $this->field('fait_a', 'text', "Fait à", "Opgemaakt te");
        }

        if ($this->isMissing($demande->date_assemblee ?? null)) {
            $missing[] = $this->field('date_assemblee', 'date', "Date de l’assemblée", "Datum van de vergadering");
        }

        return $missing;
    }

    private function getFormulaire1Fields(Demande $demande): array
    {
        $fields = $this->getPdfFields($demande);
        $bceData = $this->getBceData($demande);
        $list = [];

        $enterpriseNumber = $fields['enterprise_number'] ?? $this->getBceValue($bceData, 'number');
        $list[] = $this->fieldWithValue('pdf_enterprise_number', 'text', "Numéro d’entreprise", "Ondernemingsnummer", $enterpriseNumber);

        $denomination = $fields['denomination'] ?? $this->getBceValue($bceData, 'denomination.description.value');
        $list[] = $this->fieldWithValue('pdf_denomination', 'text', "Dénomination", "Benaming", $denomination);

        $juridicalForm = $fields['juridical_form'] ?? $this->getBceValue($bceData, 'juridicalForm.description.value');
        $list[] = $this->fieldWithValue('pdf_juridical_form', 'text', "Forme légale", "Rechtsvorm", $juridicalForm);

        $addressStreet = $fields['form1_address_street'] ?? $this->getBceValue($bceData, 'address.street.description.value');
        $list[] = $this->fieldWithValue('pdf_form1_address_street', 'text', "Rue (siège)", "Straat (zetel)", $addressStreet);

        $addressHouseNumber = $fields['form1_address_house_number'] ?? $this->getBceValue($bceData, 'address.houseNumber');
        $list[] = $this->fieldWithValue('pdf_form1_address_house_number', 'text', "Numéro (siège)", "Nummer (zetel)", $addressHouseNumber);

        $addressBox = $fields['form1_address_box'] ?? $this->getBceValue($bceData, 'address.box');
        $list[] = $this->fieldWithValue('pdf_form1_address_box', 'text', "Boîte (siège)", "Bus (zetel)", $addressBox);

        $addressZipcode = $fields['form1_address_zipcode'] ?? $this->getBceValue($bceData, 'address.zipcode');
        $list[] = $this->fieldWithValue('pdf_form1_address_zipcode', 'text', "Code postal (siège)", "Postcode (zetel)", $addressZipcode);

        $addressMunicipality = $fields['form1_address_municipality'] ?? $this->getBceValue($bceData, 'address.municipality.description.value');
        $list[] = $this->fieldWithValue('pdf_form1_address_municipality', 'text', "Localité (siège)", "Gemeente (zetel)", $addressMunicipality);

        $addressCountry = $fields['form1_address_country'] ?? $this->getBceValue($bceData, 'address.country');
        if ($this->isMissing($addressCountry)) {
            $addressCountry = $this->getDefaultCountry($demande);
        }
        $list[] = $this->fieldWithValue('pdf_form1_address_country', 'text', "Pays (siège)", "Land (zetel)", $addressCountry);

        $device = $fields['attestation_device'] ?? $this->getBceValue($bceData, 'capital.currency') ?? 'EUR';
        $bDate = $this->getBceValue($bceData, 'period.begin');
        $exerciceSocial = $this->formatExerciceSocial($bceData);
        $assembleeGenerale = $this->getBceValue($bceData, 'financialData.annualMeetingMonth');

        $list[] = $this->fieldWithValue('pdf_attestation_device', 'text', "Devise", "Valuta", $device);
        $list[] = $this->fieldWithValue('pdf_attestation_b_date', 'date', "Date de l’acte constitutif", "Datum van de oprichtingsakte", $bDate);
        $list[] = $this->fieldWithValue('pdf_attestation_exercice_social', 'text', "Exercice social (fin)", "Boekjaar (einde)", $exerciceSocial);
        $list[] = $this->fieldWithValue('pdf_attestation_assemblee_generale', 'text', "Assemblée générale", "Algemene vergadering", $assembleeGenerale);

        $list[] = $this->fieldWithValue('fait_a', 'text', "Fait à", "Opgemaakt te", $demande->fait_a ?? null);
        $list[] = $this->fieldWithValue('date_assemblee', 'date', "Date de l’assemblée", "Datum van de vergadering", $demande->date_assemblee ?? null);

        return $list;
    }

    private function getMissingFieldsFormulaire2(Demande $demande): array
    {
        $fields = $this->getPdfFields($demande);
        $bceData = $this->getBceData($demande);
        $missing = [];
        $addressServiceEnabled = (bool) $demande->address_service;

        $enterpriseNumber = $fields['enterprise_number'] ?? $this->getBceValue($bceData, 'number');
        if ($this->isMissing($enterpriseNumber)) {
            $missing[] = $this->field('pdf_enterprise_number', 'text', "Numéro d’entreprise", "Ondernemingsnummer");
        }

        $denomination = $fields['denomination'] ?? $this->getBceValue($bceData, 'denomination.description.value');
        if ($this->isMissing($denomination)) {
            $missing[] = $this->field('pdf_denomination', 'text', "Nom actuel", "Huidige naam");
        }

        $newCompanyName = $fields['attestation_company_name'] ?? null;
        if ($this->isMissing($newCompanyName)) {
            $missing[] = $this->field('pdf_attestation_company_name', 'text', "Nouveau nom", "Nieuwe naam");
        }

        $attestationIdentifier = $fields['attestation_identifier'] ?? null;
        if ($this->isMissing($attestationIdentifier)) {
            $missing[] = $this->field('pdf_attestation_identifier', 'text', "N° d’entreprise (attestation)", "Ondernemingsnummer (attest)");
        }

        $sigle = $fields['attestation_sigle'] ?? null;
        if ($this->isMissing($sigle)) {
            $missing[] = $this->field('pdf_attestation_sigle', 'text', "Sigle", "Afkorting");
        }

        $secondaryStreet = $fields['attestation_rue2'] ?? null;
        if ($this->isMissing($secondaryStreet)) {
            $missing[] = $this->field('pdf_attestation_rue2', 'text', "Rue (succursale)", "Straat (filiaal)");
        }

        $secondaryNumber = $fields['attestation_n2'] ?? null;
        if ($this->isMissing($secondaryNumber)) {
            $missing[] = $this->field('pdf_attestation_n2', 'text', "Numéro (succursale)", "Nummer (filiaal)");
        }

        $secondaryBox = $fields['attestation_boite2'] ?? null;
        if ($this->isMissing($secondaryBox)) {
            $missing[] = $this->field('pdf_attestation_boite2', 'text', "Boîte (succursale)", "Bus (filiaal)");
        }

        $secondaryPostal = $fields['attestation_code_postal2'] ?? null;
        if ($this->isMissing($secondaryPostal)) {
            $missing[] = $this->field('pdf_attestation_code_postal2', 'text', "Code postal (succursale)", "Postcode (filiaal)");
        }

        $secondaryLocalite = $fields['attestation_localite2'] ?? null;
        if ($this->isMissing($secondaryLocalite)) {
            $missing[] = $this->field('pdf_attestation_localite2', 'text', "Localité (succursale)", "Gemeente (filiaal)");
        }

        $juridicalForm = $fields['juridical_form'] ?? $this->getBceValue($bceData, 'juridicalForm.description.value');
        if ($this->isMissing($juridicalForm)) {
            $missing[] = $this->field('pdf_juridical_form', 'text', "Forme légale", "Rechtsvorm");
        }

        if ($addressServiceEnabled) {
            $form2Address = $this->buildFormulaire2Address($demande);
            if ($this->isMissing($form2Address['rue'] ?? null)) {
                $missing[] = $this->field('pdf_form2_address_rue', 'text', "Rue (siège)", "Straat (zetel)");
            }
            if ($this->isMissing($form2Address['numero'] ?? null)) {
                $missing[] = $this->field('pdf_form2_address_numero', 'text', "Numéro (siège)", "Nummer (zetel)");
            }
            $form2BoxSource = $fields['form2_address_box'] ?? ($demande->address?->box ?? null);
            if ($this->isFilled($form2BoxSource) && $this->isMissing($form2Address['box'] ?? null)) {
                $missing[] = $this->field('pdf_form2_address_box', 'text', "Boîte (siège)", "Bus (zetel)");
            }
            if ($this->isMissing($form2Address['zip_code'] ?? null)) {
                $missing[] = $this->field('pdf_form2_address_zip_code', 'text', "Code postal (siège)", "Postcode (zetel)");
            }
            if ($this->isMissing($form2Address['localite'] ?? null)) {
                $missing[] = $this->field('pdf_form2_address_localite', 'text', "Localité (siège)", "Gemeente (zetel)");
            }
            if ($this->isMissing($form2Address['pays'] ?? null)) {
                $missing[] = $this->field('pdf_form2_address_pays', 'text', "Pays (siège)", "Land (zetel)");
            }
        }

        $capitalCurrency = $fields['capital_currency'] ?? $this->getBceValue($bceData, 'capital.currency') ?? 'EUR';
        if ($this->isMissing($capitalCurrency)) {
            $missing[] = $this->field('pdf_capital_currency', 'text', "Devise du capital", "Valuta kapitaal");
        }

        $bceCapitalAmount = $this->getBceValue($bceData, 'capital.amount');
        $demandeCapitalAmount = $demande->montant ?? null;
        $capitalAmount = $fields['capital_amount'] ?? (
            $this->isFilled($bceCapitalAmount) && $this->isFilled($demandeCapitalAmount)
                ? $demandeCapitalAmount
                : ($demandeCapitalAmount ?? $bceCapitalAmount)
        );
        if ($this->isMissing($capitalAmount)) {
            $missing[] = $this->field('pdf_capital_amount', 'number', "Montant du capital", "Kapitaalbedrag");
        }

        $fiscalYearEndDay = $fields['fiscal_year_end_day'] ?? $this->getBceValue($bceData, 'financialData.fiscalYearEndDay');
        if ($this->isMissing($fiscalYearEndDay)) {
            $missing[] = $this->field('pdf_fiscal_year_end_day', 'number', "Jour de clôture", "Einde dag");
        }

        $fiscalYearEndMonth = $fields['fiscal_year_end_month'] ?? $this->getBceValue($bceData, 'financialData.fiscalYearEndMonth');
        if ($this->isMissing($fiscalYearEndMonth)) {
            $missing[] = $this->field('pdf_fiscal_year_end_month', 'number', "Mois de clôture", "Einde maand");
        }

        $annualMeetingMonth = $fields['annual_meeting_month'] ?? $this->getBceValue($bceData, 'financialData.annualMeetingMonth');
        if ($this->isMissing($annualMeetingMonth)) {
            $missing[] = $this->field('pdf_annual_meeting_month', 'text', "Mois de l’assemblée", "Maand algemene vergadering");
        }

        $dateDissolution = $fields['attestation_date_de_dissolution'] ?? null;
        if ($this->isMissing($dateDissolution)) {
            $missing[] = $this->field('pdf_attestation_date_de_dissolution', 'date', "Date de dissolution", "Datum ontbinding");
        }

        $dateCloture = $fields['attestation_date_de_cloture'] ?? null;
        if ($this->isMissing($dateCloture)) {
            $missing[] = $this->field('pdf_attestation_date_de_cloture', 'date', "Date de clôture", "Datum afsluiting");
        }



        if ($this->isMissing($demande->fait_a ?? null)) {
            $missing[] = $this->field('fait_a', 'text', "Fait à", "Opgemaakt te");
        }

        if ($this->isMissing($demande->date_assemblee ?? null)) {
            $missing[] = $this->field('date_assemblee', 'date', "Date de l’assemblée", "Datum van de vergadering");
        }

        return $missing;
    }

    private function getFormulaire2Fields(Demande $demande): array
    {
        $fields = $this->getPdfFields($demande);
        $bceData = $this->getBceData($demande);
        $list = [];

        $enterpriseNumber = $fields['enterprise_number'] ?? $this->getBceValue($bceData, 'number');
        $list[] = $this->fieldWithValue('pdf_enterprise_number', 'text', "Numéro d’entreprise", "Ondernemingsnummer", $enterpriseNumber);

        $denomination = $fields['denomination'] ?? $this->getBceValue($bceData, 'denomination.description.value');
        $list[] = $this->fieldWithValue('pdf_denomination', 'text', "Nom actuel", "Huidige naam", $denomination);

        $list[] = $this->fieldWithValue('pdf_attestation_company_name', 'text', "Nouveau nom", "Nieuwe naam", $fields['attestation_company_name'] ?? null);
        $list[] = $this->fieldWithValue('pdf_attestation_identifier', 'text', "N° d’entreprise (attestation)", "Ondernemingsnummer (attest)", $fields['attestation_identifier'] ?? null);
        $list[] = $this->fieldWithValue('pdf_attestation_sigle', 'text', "Sigle", "Afkorting", $fields['attestation_sigle'] ?? null);
        $list[] = $this->fieldWithValue('pdf_attestation_rue2', 'text', "Rue (succursale)", "Straat (filiaal)", $fields['attestation_rue2'] ?? null);
        $list[] = $this->fieldWithValue('pdf_attestation_n2', 'text', "Numéro (succursale)", "Nummer (filiaal)", $fields['attestation_n2'] ?? null);
        $list[] = $this->fieldWithValue('pdf_attestation_boite2', 'text', "Boîte (succursale)", "Bus (filiaal)", $fields['attestation_boite2'] ?? null);
        $list[] = $this->fieldWithValue('pdf_attestation_code_postal2', 'text', "Code postal (succursale)", "Postcode (filiaal)", $fields['attestation_code_postal2'] ?? null);
        $list[] = $this->fieldWithValue('pdf_attestation_localite2', 'text', "Localité (succursale)", "Gemeente (filiaal)", $fields['attestation_localite2'] ?? null);

        $juridicalForm = $fields['juridical_form'] ?? $this->getBceValue($bceData, 'juridicalForm.description.value');
        $list[] = $this->fieldWithValue('pdf_juridical_form', 'text', "Forme légale", "Rechtsvorm", $juridicalForm);

        $form2Address = $this->buildFormulaire2Address($demande);
        $list[] = $this->fieldWithValue('pdf_form2_address_rue', 'text', "Rue (siège)", "Straat (zetel)", $form2Address['rue'] ?? null);
        $list[] = $this->fieldWithValue('pdf_form2_address_numero', 'text', "Numéro (siège)", "Nummer (zetel)", $form2Address['numero'] ?? null);
        $list[] = $this->fieldWithValue('pdf_form2_address_box', 'text', "Boîte (siège)", "Bus (zetel)", $form2Address['box'] ?? null);
        $list[] = $this->fieldWithValue('pdf_form2_address_zip_code', 'text', "Code postal (siège)", "Postcode (zetel)", $form2Address['zip_code'] ?? null);
        $list[] = $this->fieldWithValue('pdf_form2_address_localite', 'text', "Localité (siège)", "Gemeente (zetel)", $form2Address['localite'] ?? null);
        $list[] = $this->fieldWithValue('pdf_form2_address_pays', 'text', "Pays (siège)", "Land (zetel)", $form2Address['pays'] ?? null);

        $capitalCurrency = $fields['capital_currency'] ?? $this->getBceValue($bceData, 'capital.currency') ?? 'EUR';
        $list[] = $this->fieldWithValue('pdf_capital_currency', 'text', "Devise du capital", "Valuta kapitaal", $capitalCurrency);

        $bceCapitalAmount = $this->getBceValue($bceData, 'capital.amount');
        $demandeCapitalAmount = $demande->montant ?? null;
        $capitalAmount = $fields['capital_amount'] ?? (
            $this->isFilled($bceCapitalAmount) && $this->isFilled($demandeCapitalAmount)
                ? $demandeCapitalAmount
                : ($demandeCapitalAmount ?? $bceCapitalAmount)
        );
        $list[] = $this->fieldWithValue('pdf_capital_amount', 'number', "Montant du capital", "Kapitaalbedrag", $capitalAmount);

        $fiscalYearEndDay = $fields['fiscal_year_end_day'] ?? $this->getBceValue($bceData, 'financialData.fiscalYearEndDay');
        $list[] = $this->fieldWithValue('pdf_fiscal_year_end_day', 'number', "Jour de clôture", "Einde dag", $fiscalYearEndDay);

        $fiscalYearEndMonth = $fields['fiscal_year_end_month'] ?? $this->getBceValue($bceData, 'financialData.fiscalYearEndMonth');
        $list[] = $this->fieldWithValue('pdf_fiscal_year_end_month', 'number', "Mois de clôture", "Einde maand", $fiscalYearEndMonth);

        $annualMeetingMonth = $fields['annual_meeting_month'] ?? $this->getBceValue($bceData, 'financialData.annualMeetingMonth');
        $list[] = $this->fieldWithValue('pdf_annual_meeting_month', 'text', "Mois de l’assemblée", "Maand algemene vergadering", $annualMeetingMonth);

        $list[] = $this->fieldWithValue('pdf_attestation_date_de_dissolution', 'date', "Date de dissolution", "Datum ontbinding", $fields['attestation_date_de_dissolution'] ?? null);
        $list[] = $this->fieldWithValue('pdf_attestation_date_de_cloture', 'date', "Date de clôture", "Datum afsluiting", $fields['attestation_date_de_cloture'] ?? null);


        $list[] = $this->fieldWithValue('fait_a', 'text', "Fait à", "Opgemaakt te", $demande->fait_a ?? null);
        $list[] = $this->fieldWithValue('date_assemblee', 'date', "Date de l’assemblée", "Datum van de vergadering", $demande->date_assemblee ?? null);

        return $list;
    }

    private function field(string $key, string $type, string $labelFr, string $labelNl): array
    {
        return [
            'key' => $key,
            'type' => $type,
            'label_fr' => $labelFr,
            'label_nl' => $labelNl,
        ];
    }

    private function getDefaultCountry(Demande $demande): string
    {
        return $demande->langue_entreprise === 'nl' ? 'België' : 'Belgique';
    }

    private function getBceValue(array $bceData, string $path)
    {
        return data_get($bceData, $path);
    }

    private function normalizeBceData(array $bceData): array
    {
        $bceData['denomination'] = $this->normalizeBceNode($bceData['denomination'] ?? null);
        if ($this->isMissing(data_get($bceData, 'denomination.description.value'))) {
            $primary = $this->normalizeBceNode($bceData['primaryDenomination'] ?? null);
            if (!$this->isMissing(data_get($primary, 'description.value'))) {
                $bceData['denomination'] = $primary;
            }
        }

        $bceData['juridicalForm'] = $this->normalizeBceNode($bceData['juridicalForm'] ?? null);

        $bceData['address'] = $this->normalizeBceNode($bceData['address'] ?? null);
        if (isset($bceData['address']['street'])) {
            $bceData['address']['street'] = $this->normalizeBceNode($bceData['address']['street']);
        }
        if (isset($bceData['address']['municipality'])) {
            $bceData['address']['municipality'] = $this->normalizeBceNode($bceData['address']['municipality']);
        }
        if (isset($bceData['address']['country']) && is_array($bceData['address']['country'])) {
            $country = $bceData['address']['country'];
            if (array_is_list($country)) {
                $country = $country[0] ?? $country;
            }
            if (is_array($country)) {
                $country = $country['description']['value']
                    ?? $country['value']
                    ?? $country['name']
                    ?? $country['code']
                    ?? null;
            }
            $bceData['address']['country'] = $country;
        }

        return $bceData;
    }

    private function normalizeBceNode($node)
    {
        if (!is_array($node)) {
            return $node;
        }
        if (array_is_list($node)) {
            $node = $node[0] ?? [];
        }
        if (isset($node['description']) && is_array($node['description']) && array_is_list($node['description'])) {
            $node['description'] = $node['description'][0] ?? $node['description'];
        }
        return $node;
    }

    private function formatExerciceSocial(array $bceData): ?string
    {
        $day = $this->getBceValue($bceData, 'financialData.fiscalYearEndDay');
        $month = $this->getBceValue($bceData, 'financialData.fiscalYearEndMonth');
        if ($this->isMissing($day) || $this->isMissing($month)) {
            return null;
        }
        $day = str_pad((string) $day, 2, '0', STR_PAD_LEFT);
        $month = str_pad((string) $month, 2, '0', STR_PAD_LEFT);
        return $day . '/' . $month;
    }

    private function fieldWithValue(string $key, string $type, string $labelFr, string $labelNl, $value): array
    {
        if ($type === 'date') {
            $value = $this->normalizeDateValue($value);
        }

        return [
            'key' => $key,
            'type' => $type,
            'label_fr' => $labelFr,
            'label_nl' => $labelNl,
            'value' => $value,
            'is_missing' => $this->isMissing($value),
        ];
    }

    private function normalizeDateValue($value): ?string
    {
        if ($this->isMissing($value)) {
            return null;
        }
        if (is_string($value)) {
            $trimmed = trim($value);
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $trimmed)) {
                return $trimmed;
            }
            if (preg_match('/^\d{4}-\d{2}-\d{2}\s+/', $trimmed)) {
                return substr($trimmed, 0, 10);
            }
        }

        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return is_string($value) ? $value : null;
        }
    }

    private function applyOverride(array &$data, array $path, $value): void
    {
        if (!$this->isFilled($value)) {
            return;
        }

        $ref = &$data;
        foreach ($path as $index => $segment) {
            if ($index === count($path) - 1) {
                $ref[$segment] = $value;
                return;
            }
            if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                $ref[$segment] = [];
            }
            $ref = &$ref[$segment];
        }
    }

    private function isMissing($value): bool
    {
        if (is_array($value)) {
            return empty($value);
        }
        if (is_string($value)) {
            return trim($value) === '';
        }
        return $value === null || $value === '';
    }

    private function isFilled($value): bool
    {
        return !$this->isMissing($value);
    }

    /**
     * Ensure all address  fields' leaf values are converted to strings to prevent htmlspecialchars errors
     * Preserves nested structure for template compatibility
     */
    private function ensureAddressStrings(array $data): array
    {
        if (!isset($data['address']) || !is_array($data['address'])) {
            return $data;
        }

        $address = $data['address'];
        
        // Helper function to safely convert to string
        $toString = function($value) {
            if (is_array($value) || is_object($value)) {
                if (is_array($value)) {
                    // Try common keys first
                    return $value['value'] ?? $value['text'] ?? $value['name'] ?? (string)implode(' ', $value);
                } else {
                    // Object - try common properties
                    return $value->value ?? $value->text ?? $value->name ?? (string)$value;
                }
            }
            return (string)$value;
        };

        // Recursively ensure all address values are strings
        $address = $this->recursiveStringConvert($address, $toString);

        $data['address'] = $address;
        return $data;
    }

    /**
     * Recursively convert all leaf values in an array to strings
     */
    private function recursiveStringConvert($value, $toString)
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                $result[$key] = $this->recursiveStringConvert($item, $toString);
            }
            return $result;
        } else {
            // This is a leaf value - convert it to string
            return $toString($value);
        }
    }

    /**
     * Ensure all formulaire2 address fields are converted to strings
     */
    private function ensureFormulaire2AddressStrings(array $address): array
    {
        // Helper function to safely convert to string
        $toString = function($value) {
            if (is_array($value) || is_object($value)) {
                if (is_array($value)) {
                    // Try common keys first
                    return $value['value'] ?? $value['text'] ?? $value['name'] ?? (string)implode(' ', $value);
                } else {
                    // Object - try common properties
                    return $value->value ?? $value->text ?? $value->name ?? (string)$value;
                }
            }
            return (string)$value;
        };

        // Convert all address fields to strings
        $fields = ['rue', 'numero', 'box', 'zip_code', 'localite', 'pays'];
        foreach ($fields as $field) {
            if (isset($address[$field]) && !empty($address[$field])) {
                $address[$field] = $toString($address[$field]);
            }
        }

        return $address;
    }
}
