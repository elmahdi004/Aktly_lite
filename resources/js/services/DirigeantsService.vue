<template>
    <div class="min-h-screen w-full p-6">
        <div class="titles text-center">
            <h1 class="text-2xl font-bold">{{ t("dirigeants.title") }}</h1>
        </div>
        <p class="text-[#64748b] text-sm mt-4 text-center">
            {{ t("dirigeants.subtitle") }}
        </p>
        <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg overflow-hidden mt-4">
            <div class="p-6">
                <!-- Bouton d'ajout -->
                <button v-if="
                    !showAddForm && !showEditForm && !showVerificationForm
                " @click="showAddForm = true"
                    class="bg-indigo-600 text-white px-4 py-2 rounded-md mb-6 hover:bg-indigo-700 transition-colors">
                    {{ t("dirigeants.addButton") }}
                </button>

                <!-- Formulaire d'ajout -->
                <div v-if="showAddForm" class="bg-gray-50 p-4 rounded-md mb-6 border border-gray-200">
                    <h2 class="text-lg font-semibold mb-4">
                        {{ t("dirigeants.addFormTitle") }}
                    </h2>
                    <div class="space-y-8">
                        <div class="row">
                            <div class="col-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("dirigeants.lastname")
                                    }}</label>
                                <input type="text" v-model="newDirector.lastname"
                                    class="w-full p-2 border border-gray-300 rounded-md" :placeholder="t('dirigeants.lastnamePlaceholder')
                                        " />
                            </div>
                            <div class="col-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("dirigeants.firstname")
                                    }}</label>
                                <input type="text" v-model="newDirector.firstname"
                                    class="w-full p-2 border border-gray-300 rounded-md" :placeholder="t('dirigeants.firstnamePlaceholder')
                                        " />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("dirigeants.function")
                                    }}<span class="text-red-500 ml-1"></span></label>
                                <select v-model="newDirector.function" required
                                    class="w-full p-2 border border-gray-300 rounded-md">
                                    <option value="" disabled selected hidden>{{ t('dirigeants.selectFunction') }}
                                    </option>
                                    <option v-for="option in functionOptions" :key="option" :value="option">
                                        {{ option }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("dirigeants.rc")
                                    }}</label>
                                <input type="text" v-model="newDirector.rc"
                                    class="w-full p-2 border border-gray-300 rounded-md" :placeholder="t('dirigeants.rcPlaceholder')
                                        " />
                            </div>
                        </div>
                        <!-- MODE SELECTION -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <label class="form-label small text-muted mb-3 font-semibold">
                                    {{ $t('step2.verificationMethod') }}
                                </label>
                                <div class="flex gap-3 mb-4">
                                    <button
                                        type="button"
                                        @click="verificationMode = 'manual'"
                                        :class="[
                                            'flex-1 px-4 py-3 rounded-lg border-2 transition-all duration-200 flex items-center justify-center gap-2',
                                            verificationMode === 'manual'
                                                ? 'border-[#059669] bg-[#059669]/10 text-[#059669] font-semibold'
                                                : 'border-gray-300 bg-white text-gray-700 hover:border-[#059669]/50'
                                        ]"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                        {{ $t('step2.manualUpload') }}
                                    </button>
                                    <button
                                        type="button"
                                        @click="verificationMode = 'idanalyzer'"
                                        :class="[
                                            'flex-1 px-4 py-3 rounded-lg border-2 transition-all duration-200 flex items-center justify-center gap-2',
                                            verificationMode === 'idanalyzer'
                                                ? 'border-[#059669] bg-[#059669]/10 text-[#059669] font-semibold'
                                                : 'border-gray-300 bg-white text-gray-700 hover:border-[#059669]/50'
                                        ]"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                        {{ $t('step2.idAnalyzer') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- MANUAL UPLOAD SECTION -->
                        <div v-if="verificationMode === 'manual'" class="row mt-2">
                            <div class="col-12">
                                <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                    <label class="form-label small text-muted font-semibold">
                                        {{ $t('step2.uploadCI') }}
                                    </label>
                                    <div class="mt-3">
                                        <div class="relative">
                                            <input type="file" id="carteIdentite" accept=".pdf,image/*"
                                                class="opacity-0 absolute w-full h-full cursor-pointer z-10"
                                                @change="(e) => {
                                                    const file = e.target.files[0];
                                                    if (file) {
                                                        newDirector.carte_identite = file;
                                                        const reader = new FileReader();
                                                        reader.onload = (event) => {
                                                            newDirectorDocumentUrl.value = event.target.result;
                                                        };
                                                        reader.readAsDataURL(file);
                                                    } else {
                                                        newDirectorDocumentUrl.value = '';
                                                    }
                                                }" />
                                            <div class="border-2 border-dashed border-gray-300 rounded-lg px-4 py-6 bg-white hover:border-[#059669] transition-colors flex flex-col items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                </svg>
                                                <span class="text-sm text-gray-600">{{ newDirector.carte_identite?.name || $t('step2.dropFileHere') }}</span>
                                                <span class="text-xs text-gray-400 mt-1">{{ $t('step2.orClickToBrowse') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Image Preview for manually uploaded file -->
                                    <div v-if="newDirectorDocumentUrl" class="border-2 border-[#059669] rounded-lg p-3 bg-white mt-3">
                                        <img :src="newDirectorDocumentUrl" alt="Document preview" class="w-full h-40 object-contain rounded" />
                                        <div class="text-xs text-gray-600 mt-2 font-medium text-center">{{ $t('step2.preview') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- ID ANALYZER SECTION -->
                        <div v-if="verificationMode === 'idanalyzer'" class="row mt-2">
                            <div class="col-12">
                                <div class="p-4 bg-gradient-to-br from-[#059669]/5 to-[#6ee7b7]/10 rounded-lg border border-[#059669]/20">
                                    <div class="flex items-start gap-3 mb-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#059669] flex-shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <div>
                                            <h4 class="font-semibold text-gray-800">{{ $t('step2.idAnalyzerTitle') }}</h4>
                                            <p class="text-sm text-gray-600 mt-1">{{ $t('step2.idAnalyzerDescription') }}</p>
                                        </div>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-6">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                {{ t("dirigeants.gsm") }}
                                            </label>
                                            <input type="text" v-model="newDirector.gsm"
                                                class="w-full p-2 border border-gray-300 rounded-md"
                                                :placeholder="t('step2.phonePlaceholder')" />
                                        </div>
                                        <div class="col-6 flex items-end justify-end">
                                            <button @click="sendIdentification" type="button" :disabled="verificationLoading || !newDirector.gsm"
                                                class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors font-semibold shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
                                                <span v-if="!verificationLoading" class="flex items-center gap-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                    </svg>
                                                    {{ t("dirigeants.identification") }}
                                                </span>
                                                <span v-else class="flex items-center gap-2">
                                                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                                    </svg>
                                                    {{ t("dirigeants.identificationSent") }}
                                                </span>
                                            </button>
                                        </div>
                                    </div>

                                    <p v-if="verificationSent" class="text-success text-sm mt-3 flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ t("dirigeants.identificationSent") }}
                                    </p>
                                    <p v-if="errorveriff" class="text-danger text-sm mt-3 flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ errorveriff }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex gap-2 mt-4">
                            <button @click="handleAddDirector"
                                class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors">
                                {{ t("dirigeants.save") }}
                            </button>
                            <button @click="handleCancel"
                                class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition-colors">
                                {{ t("dirigeants.cancel") }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Formulaire de modification -->
                <div v-if="showEditForm && currentDirector"
                    class="bg-gray-50 p-4 rounded-md mb-6 border border-gray-200">
                    <h2 class="text-lg font-semibold mb-4">
                        {{ t("dirigeants.edit") }} {{ t("dirigeants.title") }}
                    </h2>
                    <div class="space-y-4">
                        <div class="row">
                            <div class="col-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("dirigeants.lastname")
                                    }}</label>
                                <input type="text" v-model="currentDirector.surname"
                                    class="w-full p-2 border border-gray-300 rounded-md"
                                    :disabled="currentDirector.is_modified === 0" />
                            </div>
                            <div class="col-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("dirigeants.firstname")
                                    }}</label>
                                <input type="text" v-model="currentDirector.givenName"
                                    class="w-full p-2 border border-gray-300 rounded-md"
                                    :disabled="currentDirector.is_modified === 0" />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("dirigeants.function")
                                    }}<span class="text-red-500 ml-1"></span></label>
                                <select v-model="currentDirector.function" required
                                    class="w-full p-2 border border-gray-300 rounded-md"
                                    :class="{ 'bg-gray-100 cursor-not-allowed': currentDirector.is_modified === 0 }"
                                    :disabled="currentDirector.is_modified === 0">
                                    <option value="" disabled selected hidden>{{ t('dirigeants.selectFunction') }}
                                    </option>
                                    <option v-for="option in functionOptions" :key="option" :value="option">
                                        {{ option }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("dirigeants.rc")
                                    }}</label>
                                <input type="text" v-model="currentDirector.rc"
                                    class="w-full p-2 border border-gray-300 rounded-md" />
                            </div>
                        </div>
                        <!-- MODE SELECTION FOR EDIT -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <label class="form-label small text-muted mb-3 font-semibold">
                                    {{ $t('step2.verificationMethod') }}
                                </label>
                                <div class="flex gap-3 mb-4">
                                    <button
                                        type="button"
                                        @click="verificationModeEdit = 'manual'"
                                        :class="[
                                            'flex-1 px-4 py-3 rounded-lg border-2 transition-all duration-200 flex items-center justify-center gap-2',
                                            verificationModeEdit === 'manual'
                                                ? 'border-[#059669] bg-[#059669]/10 text-[#059669] font-semibold'
                                                : 'border-gray-300 bg-white text-gray-700 hover:border-[#059669]/50'
                                        ]"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                        {{ $t('step2.manualUpload') }}
                                    </button>
                                    <button
                                        type="button"
                                        @click="verificationModeEdit = 'idanalyzer'"
                                        :class="[
                                            'flex-1 px-4 py-3 rounded-lg border-2 transition-all duration-200 flex items-center justify-center gap-2',
                                            verificationModeEdit === 'idanalyzer'
                                                ? 'border-[#059669] bg-[#059669]/10 text-[#059669] font-semibold'
                                                : 'border-gray-300 bg-white text-gray-700 hover:border-[#059669]/50'
                                        ]"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                        {{ $t('step2.idAnalyzer') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- MANUAL UPLOAD SECTION FOR EDIT -->
                        <div v-if="verificationModeEdit === 'manual'" class="row mt-2">
                            <div class="col-12">
                                <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                    <label class="form-label small text-muted font-semibold">
                                        {{ $t('step2.uploadCI') }}
                                    </label>
                                    <div class="mt-3">
                                        <div class="relative">
                                            <input type="file" id="carteIdentiteEdit" accept=".pdf,image/*"
                                                class="opacity-0 absolute w-full h-full cursor-pointer z-10"
                                                @change="(e) => {
                                                    const file = e.target.files[0];
                                                    if (file) {
                                                        currentDirector.carte_identite = file;
                                                        const reader = new FileReader();
                                                        reader.onload = (event) => {
                                                            currentDirectorDocumentUrl.value = event.target.result;
                                                        };
                                                        reader.readAsDataURL(file);
                                                    } else {
                                                        currentDirectorDocumentUrl.value = '';
                                                    }
                                                }" />
                                            <div class="border-2 border-dashed border-gray-300 rounded-lg px-4 py-6 bg-white hover:border-[#059669] transition-colors flex flex-col items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                </svg>
                                                <span class="text-sm text-gray-600">{{ currentDirector.carte_identite?.name || $t('step2.dropFileHere') }}</span>
                                                <span class="text-xs text-gray-400 mt-1">{{ $t('step2.orClickToBrowse') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Image Preview for manually uploaded file -->
                                    <div v-if="currentDirectorDocumentUrl" class="border-2 border-[#059669] rounded-lg p-3 bg-white mt-3">
                                        <img :src="currentDirectorDocumentUrl" alt="Document preview" class="w-full h-40 object-contain rounded" />
                                        <div class="text-xs text-gray-600 mt-2 font-medium text-center">{{ $t('step2.preview') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- ID ANALYZER SECTION FOR EDIT -->
                        <div v-if="verificationModeEdit === 'idanalyzer'" class="row mt-2">
                            <div class="col-12">
                                <div class="p-4 bg-gradient-to-br from-[#059669]/5 to-[#6ee7b7]/10 rounded-lg border border-[#059669]/20">
                                    <div class="flex items-start gap-3 mb-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#059669] flex-shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <div>
                                            <h4 class="font-semibold text-gray-800">{{ $t('step2.idAnalyzerTitle') }}</h4>
                                            <p class="text-sm text-gray-600 mt-1">{{ $t('step2.idAnalyzerDescription') }}</p>
                                        </div>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-6">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                {{ t("dirigeants.gsm") }}
                                            </label>
                                            <input type="text" v-model="currentDirector.gsm"
                                                class="w-full p-2 border border-gray-300 rounded-md"
                                                :placeholder="t('step2.phonePlaceholder')" />
                                        </div>
                                        <div class="col-6 flex items-end justify-end">
                                            <button @click="sendIdentificationForCurrent" type="button" :disabled="verificationLoadingForCurrent || !currentDirector.gsm"
                                                class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors font-semibold shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
                                                <span v-if="!verificationLoadingForCurrent" class="flex items-center gap-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                    </svg>
                                                    {{ t("dirigeants.identification") }}
                                                </span>
                                                <span v-else class="flex items-center gap-2">
                                                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                                    </svg>
                                                    {{ t("dirigeants.identificationSent") }}
                                                </span>
                                            </button>
                                        </div>
                                    </div>

                                    <p v-if="verificationSent" class="text-success text-sm mt-3 flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ t("dirigeants.identificationSent") }}
                                    </p>
                                    <p v-if="errorveriff" class="text-danger text-sm mt-3 flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ errorveriff }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex gap-2 mt-4">
                            <button @click="handleSaveEdit"
                                class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors relative z-50">
                                {{ t("dirigeants.save") }}
                            </button>
                            <button @click="handleCancel"
                                class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition-colors">
                                {{ t("dirigeants.cancel") }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Liste des dirigeants -->
                <div v-if="
                    !showAddForm &&
                    !showEditForm &&
                    !showVerificationForm &&
                    !showVerificationFormUpdate
                " class="mt-4">
                    <h2 class="text-lg font-semibold mb-4">
                        {{ t("dirigeants.listTitle") }}
                    </h2>
                    <p class="text-sm text-gray-600 mb-3 font-semibold">
                        {{ t("dirigeants.demissionNote") }}
                    </p>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ t("dirigeants.lastname") }}
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ t("dirigeants.firstname") }}
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ t("dirigeants.function") }}
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ t("dirigeants.rc") }}
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ t("dirigeants.actions") }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="director in directors" :key="director.id">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ director.surname ?? '' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ director.givenName ?? '' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ director.function ?? '' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ director?.rc ?? '' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button @click="
                                            handleEditDirector(director)
                                            " class="text-indigo-600 hover:text-indigo-900 mr-4"
                                            :title="t('dirigeants.validateIdentity')">
                                            <img src="/valider.png" alt="Validation" class="w-6 h-6 inline-block" />
                                        </button>

                                        <button
                                            @click="canDemission(director) && handleDeleteDirector(director.id)"
                                            class="text-red-600 hover:text-red-900"
                                            :class="{ 'opacity-50 cursor-not-allowed': !canDemission(director) }"
                                            :aria-disabled="!canDemission(director)"
                                            :title="!canDemission(director) ? t('dirigeants.demissionTooltipIncomplete') : t('dirigeants.demission')">
                                            <img src="/demissionner.png" alt="Démission" class="w-6 h-6 inline-block" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="deletedDirectors.length" class="mt-8">
                        <h3 class="text-md font-semibold mb-3">{{ t("dirigeants.resignedTitle") }}</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ t("dirigeants.lastname") }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ t("dirigeants.firstname") }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ t("dirigeants.function") }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ t("dirigeants.rc") }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ t("dirigeants.status") }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ t("dirigeants.actions") }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="director in deletedDirectors" :key="director.id">
                                        <td class="px-6 py-4 whitespace-nowrap">{{ director.surname ?? '' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ director.givenName ?? '' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ director.function ?? '' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ director?.rc ?? '' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ t("dirigeants.resignedLabel") }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <button @click="handleRestoreDirector(director)" class="text-green-600 hover:text-green-900" :title="t('dirigeants.restore')">
                                                <img src="/valider.png" alt="Restaurer" class="w-6 h-6 inline-block" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div>
            <p class="text-[#726265] text-sm mt-4 text-center min-h-5">
                {{ error }}
            </p>
        </div>
        <div class="buttons flex mt-6 justify-center">
            <button :disabled="loading" @click="prevStep()" type="button"
                class="mx-4 px-10 py-2 border rounded-full text-[#059669] border-[#059669] bg-white hover:bg-[#f2f2f2]">
                {{ $t('step1Validate.previousButton') }}
            </button>
            <button @click="continueHandle()" type="submit" :disabled="loading"
                class="mx-4 px-10 py-2 border rounded-full text-white bg-[#059669] disabled:bg-[#059669]/50">
                {{
                    loading ? t("dirigeants.loading") : t("dirigeants.continue")
                }}
            </button>
        </div>
    </div>
</template>

<script setup>
import axiosInstance from "@/axios.js";
import { ref, reactive, onMounted, inject, computed } from "vue";
import { useRouter } from "vue-router";
import { useI18n } from "vue-i18n";

const { t, locale } = useI18n();

// État initial avec quelques dirigeants d'exemple
const directors = ref([]);
const deletedDirectors = ref([]);
const loadint = ref(false);
// États pour les différentes fonctionnalités
const showAddForm = ref(false);
const showEditForm = ref(false);
const showVerificationForm = ref(false);
const showVerificationFormUpdate = ref(false);
const currentDirector = ref(null);
const newDirector = ref({ firstname: "", lastname: "", function: "", rc: "", gsm: "", carte_identite: null});
const verificationContact = ref("");
const verificationSent = ref(false);
const contactType = ref("email");
const updateStep = inject("updateStep");
const loading = ref(false);
const verificationLoading = ref(false);
const verificationLoadingForCurrent = ref(false);
const router = useRouter();
const error = ref("");
const errorveriff = ref("");
const newDirectorDocumentUrl = ref("");
const currentDirectorDocumentUrl = ref("");
const verificationMode = ref("manual"); // "manual" or "idanalyzer"
const verificationModeEdit = ref("manual"); // "manual" or "idanalyzer"

const isBelgianPhoneValid = (phone) => {
    const regex = /^\+[1-9]\d{7,14}$/;
    return regex.test(phone);
};

const canDemission = (director) => {
    if (!director) return false;
    const hasSurname = (director.surname || "").trim().length > 0;
    const hasGivenName = (director.givenName || "").trim().length > 0;
    const hasFunction = (director.function || "").trim().length > 0;
    const hasRc = (director.rc || "").trim().length > 0;
    return hasSurname && hasGivenName && hasFunction && hasRc;
};

// Computed property for function options based on language
const functionOptions = computed(() => {
    const lang = locale.value || localStorage.getItem('lang') || 'fr';
    const baseOptions = lang === 'nl'
        ? ['Bestuurder', 'Gedelegeerd bestuurder']
        : ['Administrateur', 'Administrateur délégué'];

    const options = [...baseOptions];
    const addOption = (value) => {
        const trimmed = (value || '').trim();
        if (!trimmed) return;
        if (!options.includes(trimmed)) options.push(trimmed);
    };

    directors.value.forEach((director) => addOption(director?.function));
    deletedDirectors.value.forEach((director) => addOption(director?.function));
    addOption(currentDirector.value?.function);
    addOption(newDirector.value?.function);

    return options.filter((option) => {
        const normalized = (option || '').trim().toLowerCase();
        return normalized !== 'gerant' && normalized !== 'gérant';
    });
});

onMounted(async () => {
    await checkCreditsAndRedirect();
    if (updateStep) updateStep(6);
    getDirigeants();

});
const prevStep = async () => {
    const demande = await getDemande();
    console.log(demande);
    if (demande == null) {
        return;
    }
    const address_service = demande.demande.address_service;
    if (address_service == 1) {
        router.push({ name: "addressService" });
    } else {
        router.push({ name: "step3" });
    }
};
// get drigeants
const getDirigeants = async () => {
    loading.value = true;
    const demande_id = sessionStorage.getItem("demande_id") || 0;
    const response = await axiosInstance
        .get(`demandes/${demande_id}`)
        .then(async ({ data }) => {
            if (data && data.demande.dirigeants && data.demande.dirigeants.length > 0) {
                const allDirigeants = data.demande.dirigeants;
                directors.value = allDirigeants.filter(d => d.is_deleted === 0 || !d.is_deleted);
                deletedDirectors.value = allDirigeants.filter(d => d.is_deleted === 1);
                console.log(directors);
                return;
            }

            await axiosInstance.post(`dirigeantsService`, {
                demande_id: demande_id,
            });

            const refreshed = await axiosInstance.get(`demandes/${demande_id}`);
            if (refreshed.data && refreshed.data.demande.dirigeants) {
                const allDirigeants = refreshed.data.demande.dirigeants;
                directors.value = allDirigeants.filter(d => d.is_deleted === 0 || !d.is_deleted);
                deletedDirectors.value = allDirigeants.filter(d => d.is_deleted === 1);
                console.log(directors);
            }
        })
        .catch((error) => error)
        .finally(() => (loading.value = false));
};

// Gestion de l'ajout d'un nouveau dirigeant
const handleAddDirector = () => {
    if (newDirector.value.firstname && newDirector.value.lastname && newDirector.value.function && newDirector.value.rc) {
        const directorToAdd = {
            givenName: newDirector.value.firstname,
            surname: newDirector.value.lastname,
            function: newDirector.value.function,
            rc: newDirector.value.rc
        };
        currentDirector.value = directorToAdd;
        showAddForm.value = false;
        addNewDirigeant();
    } else {
        error.value = t("dirigeants.errorFunctionRequired");
    }
};

// Gestion de la modification d'un dirigeant
const handleEditDirector = (director) => {
    currentDirector.value = { ...director, gsm: director.gsm || "" };
    showEditForm.value = true;
    showAddForm.value = false;
};

// Sauvegarde des modifications
const handleSaveEdit = () => {
    if (currentDirector.value && currentDirector.value.function) {
        // gives us the indes of the dirctor on directors array
        const index = directors.value.findIndex(
            (d) => d.id === currentDirector.value.id
        );
        console.log(index);
        if (index !== -1) {
            directors.value.splice(index, 1, { ...currentDirector.value });
        }
        showEditForm.value = false;
        updateDirigeant();

    } else {
        error.value = t("dirigeants.errorFunctionRequired");
    }
};

const handleDeleteDirector = async (id) => {
    try {
        await axiosInstance.delete(`dirigeantsService/${id}`);
        const removed = directors.value.find(d => d.id === id);
        directors.value = directors.value.filter((director) => director.id !== id);
        const deleted = deletedDirectors.value.find(d => d.id === id);
        if (!deleted && removed) {
            deletedDirectors.value.push({ ...removed, is_deleted: 1 });
        }
    } catch (error) {
        console.error("Error deleting director:", error);
    }
};

const handleRestoreDirector = async (director) => {
    try {
        const formData = new FormData();
        formData.append('surname', director.surname ?? '');
        formData.append('givenName', director.givenName ?? '');
        formData.append('function', director.function ?? '');
        formData.append('rc', director.rc ?? '');
        formData.append('is_deleted', 0);

        const response = await axiosInstance.post(
            `dirigeantsService/${director.id}?_method=PUT`,
            formData,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        );

        if (response.data) {
            deletedDirectors.value = deletedDirectors.value.filter(d => d.id !== director.id);
            directors.value.push({ ...response.data, is_deleted: 0 });
        }
    } catch (error) {
        console.error("Error restoring director:", error);
    }
};

// Fermeture des formulaires
const handleCancel = () => {
    showAddForm.value = false;
    showEditForm.value = false;
    verificationSent.value = false;
    verificationContact.value = "";
    newDirector.value.firstname = "";
    newDirector.value.lastname = "";
    newDirector.value.function = "";
    newDirector.value.gsm = "";
    newDirectorDocumentUrl.value = "";
    currentDirectorDocumentUrl.value = "";
    currentDirector.value = null;
};

const sendIdentification = async () => {
    errorveriff.value = "";
    verificationSent.value = false;
    verificationLoading.value = true;

    if (!newDirector.value.gsm || !isBelgianPhoneValid(newDirector.value.gsm)) {
        errorveriff.value = t("step2.errorInvalidPhoneFormat");
        verificationLoading.value = false;
        return;
    }

    try {
        // If newDirector hasn't been saved yet, save it first to get the ID
        let dirigeantId = newDirector.value.id;
        if (!dirigeantId) {
            console.log("📝 Saving new dirigeant first...");
            // Save the dirigeant first
            const data = new FormData();
            data.append('is_not_bce', true);
            data.append('demande_id', sessionStorage.getItem("demande_id"));
            data.append('surname', newDirector.value.lastname);
            data.append('givenName', newDirector.value.firstname);
            data.append('function', newDirector.value.function);
            data.append('rc', newDirector.value.rc);
            data.append('gsm', newDirector.value.gsm);
            if (newDirector.value.carte_identite) {
                data.append('carte_identite', newDirector.value.carte_identite);
            }

            const response = await axiosInstance.post('dirigeantsService', data, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            dirigeantId = response.data.id;
            newDirector.value.id = dirigeantId;
            console.log("✅ Dirigeant saved with ID:", dirigeantId);
        }

        // Now send verification with the dirigeant_id
        console.log("📤 Sending verification for dirigeant:", dirigeantId);
        const verResponse = await axiosInstance.post("/verifications", {
            GSM: newDirector.value.gsm,
            dirigeant_id: dirigeantId,
            demande_id: sessionStorage.getItem("demande_id")
        });
        console.log("✅ Verification created:", verResponse.data);
        verificationSent.value = true;
    } catch (e) {
        console.error("Identification failed", e);
        errorveriff.value = t("step2.errorServer");
    } finally {
        verificationLoading.value = false;
    }
};

const sendIdentificationForCurrent = async () => {
    errorveriff.value = "";
    verificationSent.value = false;
    verificationLoadingForCurrent.value = true;

    if (!currentDirector.value?.gsm || !isBelgianPhoneValid(currentDirector.value.gsm)) {
        errorveriff.value = t("step2.errorInvalidPhoneFormat");
        verificationLoadingForCurrent.value = false;
        return;
    }

    try {
        const payload = {
            GSM: currentDirector.value.gsm,
            demande_id: sessionStorage.getItem("demande_id")
        };
        
        // If editing an existing dirigeant, include its ID
        if (currentDirector.value.id) {
            payload.dirigeant_id = currentDirector.value.id;
            console.log("📤 Sending verification for dirigeant:", currentDirector.value.id);
        }
        
        const verResponse = await axiosInstance.post("/verifications", payload);
        console.log("✅ Verification created:", verResponse.data);
        verificationSent.value = true;
    } catch (e) {
        console.error("Identification failed", e);
        errorveriff.value = t("step2.errorServer");
    } finally {
        verificationLoadingForCurrent.value = false;
    }
};

// add new dirigeant

const addNewDirigeant = async () => {
    loading.value = true;
    if (
        !newDirector.value.firstname ||
        !newDirector.value.lastname) {
        return;
    }
    const dataTods = {
        is_not_bce: true,
        demande_id: sessionStorage.getItem("demande_id") || 0,
        surname: newDirector.value.lastname,
        givenName: newDirector.value.firstname,
        function: newDirector.value.function,
        rc: newDirector.value.rc,
        gsm: newDirector.value.gsm,
        
    };
    const data = new FormData()

    data.append('is_not_bce', true)
    data.append('demande_id', sessionStorage.getItem("demande_id"))
    data.append('surname', newDirector.value.lastname)
    data.append('givenName', newDirector.value.firstname)
    data.append('function', newDirector.value.function)
    data.append('rc', newDirector.value.rc)
    data.append('gsm', newDirector.value.gsm)
    if (newDirector.value.carte_identite) {
        data.append('carte_identite', newDirector.value.carte_identite)
    }
    if (newDirector.value.carte_identite) {
        data.append('carte_identite', newDirector.value.carte_identite)
    }

    try {
        const response = await axiosInstance.post(`dirigeantsService`, data, { headers: { 'Content-Type': 'multipart/form-data' } });
        loading.value = false;
        console.log("response");
        console.log(response);
        directors.value.push(dataTods)
        return response.data;
    } catch (error) {
        console.log(error);
    }
    loading.value = false;
    setTimeout(() => {
            newDirector.full_name = "";
            newDirector.shares = "";
            newDirector.shares_old = "";
            currentDirector.value = null;
        }, 2000);
};

const updateDirigeant = async () => {
   

    const formData = new FormData()

    formData.append('surname', currentDirector.value.surname)
    formData.append('givenName', currentDirector.value.givenName)
    formData.append('function', currentDirector.value.function)
    formData.append('rc', currentDirector.value.rc)
    formData.append('gsm', currentDirector.value.gsm)

    if (currentDirector.value.carte_identite instanceof File) {
        formData.append(
            'carte_identite',
            currentDirector.value.carte_identite
        )
    }

    try {
        const response = await axiosInstance.post(
            `dirigeantsService/${currentDirector.value.id}?_method=PUT`,
            formData,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        )

        if (response.data) {
            const index = directors.value.findIndex(
                d => d.id === response.data.id
            )

            if (index !== -1) {
                const merged = {
                    ...directors.value[index],
                    ...response.data
                }
                directors.value.splice(index, 1, merged)
                currentDirector.value = merged
                return merged
            } else {
                directors.value.push(response.data)
                return response.data
            }
        }

        return null
    } catch (error) {
        console.error('Update dirigeant error:', error)
        return null
    }

    setTimeout(() => {
            newDirector.full_name = "";
            newDirector.shares = "";
            newDirector.shares_old = "";
            currentDirector.value = null;
        }, 2000);
}


// continue button handle :

const continueHandle = async () => {
    loading.value = true;
    const demande = await getDemande();
    if (demande == null) {
        return;
    }
    const cession_parts_service = demande.demande.cession_parts_service;
    if (cession_parts_service == 1) {
        router.push({ name: "cessionService" });
    } else {
        router.push({ name: "step4" });
    }

};

const getDemande = async () => {
    // loading.value = true;
    const demande_id = sessionStorage.getItem("demande_id") || 0;
    try {
        const response = await axiosInstance.get(`demandes/${demande_id}`);
        return response.data;
    } catch (error) {
        console.error("Error fetching address:", error);
        return null;
    } finally {
        // loading.value = false;
    }
};

const checkCreditsAndRedirect = async () => {
    try {
        const demande_id = sessionStorage.getItem("demande_id");
        if (demande_id) {
            try {
                const demandeResponse = await axiosInstance.get(`/demandes/${demande_id}`);
                if (demandeResponse.data && demandeResponse.data.demande) {
                    console.log("Resuming existing demande, skipping subscription check");
                    return true;
                }
            } catch (demandeError) {
                console.log("Demande not found or not accessible, checking subscription");
            }
        }

        const { data } = await axiosInstance.get('/billing/subscription');
        const hasValidSubscription = data.active;
        const hasValidCredits = Number(data.credits || 0) > 0;

        if (!hasValidSubscription && !hasValidCredits) {
            router.push({ name: "buyCredits" });
            return false;
        }
        return true;
    } catch (err) {
        console.error("Error checking subscription:", err);
        error.value = t('step1.errorCheckingCredits');
        return false;
    }
};
</script>
