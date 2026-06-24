<template>
    <div class="min-h-screen w-full p-6">
        <!-- Modal for choosing input method -->
        <transition name="fade">
                    <div v-if="showChoiceModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
                        <div class="bg-white rounded-2xl shadow-xl p-8 max-w-md w-full flex flex-col items-center gap-6 animate-fade-in">
                    <svg class="h-12 w-12 text-[#059669] mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 20a8 8 0 100-16 8 8 0 000 16z" />
                    </svg>
                            <h2 class="text-xl font-bold text-[#059669] text-center">{{ $t('cessionParts.chooseMethodTitle') || 'Choisissez la méthode' }}</h2>
                            <p class="text-gray-700 text-center">{{ $t('cessionParts.chooseMethodText') || 'Comment souhaitez-vous encoder les actionnaires ?' }}</p>
                    <div class="flex flex-col gap-4 w-full mt-2">
                                <button @click="chooseManual" class="px-6 py-3 bg-[#059669] text-white rounded-lg font-semibold hover:bg-[#3a36b6] transition text-lg">{{ $t('cessionParts.manualButton') || 'Encodage manuel' }}</button>
                                <button @click="chooseAI" class="px-6 py-3 bg-gradient-to-r from-[#059669] to-[#6ee7b7] text-white rounded-lg font-semibold hover:from-[#3a36b6] hover:to-[#7e89d6] transition text-lg">{{ $t('cessionParts.aiButton') || 'Utiliser l’API IA' }}</button>
                    </div>
                </div>
            </div>
        </transition>
        <div  class="mb-4 p-2 bg-yellow-100 text-yellow-800 rounded text-center font-semibold">
            <span>{{ $t('cessionParts.avertissement') }}
                <br>
                {{ $t('cessionParts.txtavertissement') }}
            </span>
        </div>
        <div class="titles text-center">
            <h1 class="text-2xl font-bold">{{ $t("cessionParts.title") }}</h1>
        </div>
        <p class="text-[#64748b] text-sm mt-4 text-center">
            {{ $t("cessionParts.subtitle") }}
        </p>
        <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg overflow-hidden mt-4">
            <div class="stats-container m-10">
                <div class="stat-card" v-for="(stat, index) in stats" :key="index">
                    <div class="stat-icon" :style="{ backgroundColor: stat.bgColor }">
                        <i :class="stat.icon"></i>
                    </div>
                    <div class="stat-info">
                        <h3>{{ $t(`${stat.title}`) }}</h3>
                        <span v-if="!stat.editing" class="stat-number">{{ stat.count }} {{ stat.device }}</span>
                        <input v-if="isEditing[index]" type="number" v-model="count[index]" class="form-control my-2" />

                        <!-- Bouton "modifier" -->
                        <i v-if="!isEditing[index]" class="fas fa-pencil text-primary" id="edit-nbshares"
                            @click="startEditing(index)"></i>

                        <!-- Bouton "valider" -->
                        <i v-if="isEditing[index]" class="text-success" @click="submitEdit(index)"
                            style="cursor: pointer;"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                fill="currentColor" class="bi bi-download" viewBox="0 0 16 16">
                                <path
                                    d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5" />
                                <path
                                    d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z" />
                            </svg>
                        </i>
                    </div>
                </div>
            </div>
            <!-- Contenu principal -->
            <div class="p-6">
                <button v-if="
                    !showAddForm && !showEditForm
                " @click="showAddForm = true" :disabled="loading || aiProcessing"
                    class="bg-indigo-600 disabled:bg-indigo-600/30 text-white px-4 py-2 rounded-md mb-6 hover:bg-indigo-700 transition-colors">
                    {{ $t("cessionParts.addButton") }}
                </button>

                <div v-if="showAddForm" class="bg-gray-50 p-4 rounded-md mb-6 border border-gray-200">
                    <h2 class="text-lg font-semibold mb-4">
                        {{ $t("cessionParts.addFormTitle") }}
                    </h2>
                    <div class="space-y-4">
                        <div class="row">
                            <div class="col-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ $t("cessionParts.fullName") }}
                                </label>
                                <input type="text" v-model="newDirector.full_name"
                                    class="w-full p-2 border border-gray-300 rounded-md" :placeholder="$t('cessionParts.fullNamePlaceholder')
                                        " />
                            </div>
                            <div class="col-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("dirigeants.rc")
                                }}</label>
                                <input type="text" v-model="newDirector.rc"
                                    class="w-full p-2 border border-gray-300 rounded-md" />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ $t("cessionParts.shares") }}
                                </label>
                                <input type="number" v-model="newDirector.shares"
                                    class="w-full p-2 border border-gray-300 rounded-md" :placeholder="$t('cessionParts.sharesPlaceholder')
                                        " />
                            </div>
                            <div class="col-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ $t("cessionParts.shares_old") }}
                                </label>
                                <input type="number" v-model="newDirector.shares_old"
                                    class="w-full p-2 border border-gray-300 rounded-md" :placeholder="$t('cessionParts.sharesOldPlaceholder')
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
                                                            newShareholderDocumentUrl.value = event.target.result;
                                                        };
                                                        reader.readAsDataURL(file);
                                                    } else {
                                                        newShareholderDocumentUrl.value = '';
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
                                    <div v-if="newShareholderDocumentUrl" class="border-2 border-[#059669] rounded-lg p-3 bg-white mt-3">
                                        <img :src="newShareholderDocumentUrl" alt="Document preview" class="w-full h-40 object-contain rounded" />
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
                                                {{ $t('cessionParts.identificationGsm') }}
                                            </label>
                                            <input type="text" v-model="newDirector.gsm"
                                                class="w-full p-2 border border-gray-300 rounded-md"
                                                :placeholder="$t('step2.phonePlaceholder')" />
                                        </div>
                                        <div class="col-6 flex items-end justify-end">
                                            <button @click="sendIdentificationShareholder" type="button" :disabled="verificationLoadingShareholder || !newDirector.gsm"
                                                class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors font-semibold shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
                                                <span v-if="!verificationLoadingShareholder" class="flex items-center gap-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                    </svg>
                                                    {{ $t('dirigeants.identification') }}
                                                </span>
                                                <span v-else class="flex items-center gap-2">
                                                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                                    </svg>
                                                    {{ $t('dirigeants.identificationSent') }}
                                                </span>
                                            </button>
                                        </div>
                                    </div>

                                    <p v-if="verificationSent" class="text-success text-sm mt-3 flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ $t('dirigeants.identificationSent') }}
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
                            <button @click="handleAddDirector" :disabled="loading"
                                class="z-40 bg-indigo-600 disabled:bg-indigo-600/30 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors">
                                {{ $t("cessionParts.add") }}
                            </button>
                            <button @click="handleCancel" :disabled="loading"
                                class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition-colors">
                                {{ $t("cessionParts.cancel") }}
                            </button>
                        </div>
                    </div>
                    <div v-if="errorMessageShares && verificationMode !== 'idanalyzer'"
                        class="mt-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative"
                        role="alert">
                        <span class="block sm:inline">{{ errorMessageShares }}</span>
                    </div>
                </div>

                <!-- Formulaire de modification -->
                <div v-if="showEditForm && currentDirector"
                    class="bg-gray-50 p-4 rounded-md mb-6 border border-gray-200">
                    <h2 class="text-lg font-semibold mb-4">
                        {{ $t("cessionParts.editFormTitle") }}
                    </h2>
                    <div class="space-y-4">
                        <div class="row">
                            <div class="col-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ $t("cessionParts.fullName") }}
                                </label>

                                <input type="text" v-model="currentDirector.full_name"
                                    class="w-full p-2 border border-gray-300 rounded-md"
                                    :disabled="currentDirector.is_origin == 1" />
                            </div>
                            <div class="col-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("dirigeants.rc")
                                    }}</label>
                                <input type="text" v-model="currentDirector.rc"
                                    class="w-full p-2 border border-gray-300 rounded-md"
                                    :disabled="currentDirector.is_modified === 0" />
                            </div>

                        </div>
                        <div class="row">
                            <div class="col-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ $t("cessionParts.shares") }}
                                </label>
                                <input type="text" v-model="currentDirector.shares"
                                    class="w-full p-2 border border-gray-300 rounded-md" />
                            </div>
                            <div class="col-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ $t("cessionParts.shares_old") }}
                                </label>
                                <input type="text" v-model="currentDirector.shares_old"
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
                                                            currentShareholderDocumentUrl.value = event.target.result;
                                                        };
                                                        reader.readAsDataURL(file);
                                                    } else {
                                                        currentShareholderDocumentUrl.value = '';
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
                                    <div v-if="currentShareholderDocumentUrl" class="border-2 border-[#059669] rounded-lg p-3 bg-white mt-3">
                                        <img :src="currentShareholderDocumentUrl" alt="Document preview" class="w-full h-40 object-contain rounded" />
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
                                                {{ $t('cessionParts.identificationGsm') }}
                                            </label>
                                            <input type="text" v-model="currentDirector.gsm"
                                                class="w-full p-2 border border-gray-300 rounded-md"
                                                :placeholder="$t('step2.phonePlaceholder')" />
                                        </div>
                                        <div class="col-6 flex items-end justify-end">
                                            <button @click="sendIdentificationShareholderEdit" type="button" :disabled="verificationLoadingShareholderEdit || !currentDirector.gsm"
                                                class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors font-semibold shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
                                                <span v-if="!verificationLoadingShareholderEdit" class="flex items-center gap-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                    </svg>
                                                    {{ $t('dirigeants.identification') }}
                                                </span>
                                                <span v-else class="flex items-center gap-2">
                                                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                                    </svg>
                                                    {{ $t('dirigeants.identificationSent') }}
                                                </span>
                                            </button>
                                        </div>
                                    </div>

                                    <p v-if="verificationSent" class="text-success text-sm mt-3 flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ $t('dirigeants.identificationSent') }}
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
                            <button @click="handleSaveEdit" :disabled="loading"
                                class="bg-indigo-600 disabled:bg-indigo-600/30 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors relative z-50">
                                {{ $t("cessionParts.save") }}
                            </button>
                            <button @click="handleCancel" :disabled="loading"
                                class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition-colors">
                                {{ $t("cessionParts.cancel") }}
                            </button>
                        </div>

                        <div v-if="errorMessageShares"
                            class="mt-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative"
                            role="alert">
                            <span class="block sm:inline">{{ errorMessageShares }}</span>
                        </div>
                    </div>
                </div>

                <!-- Liste des dirigeants -->
                <div v-if="!showAddForm && !showEditForm" class="mt-4">
                    <h2 class="text-lg font-semibold mb-4">
                        {{ $t("cessionParts.listTitle") }}
                    </h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ $t("cessionParts.fullName") }}
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ $t("cessionParts.shares") }}
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ $t("cessionParts.shares_old") }}
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ t("dirigeants.rc") }}
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ $t("cessionParts.actions") }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200"
                                v-if="aggregatedShareholders && aggregatedShareholders.length > 0">
                                <tr v-for="shareholder in aggregatedShareholders" :key="shareholder.id">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ shareholder.full_name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ shareholder.shares }}
                                        <span
                                            v-if="Number(shareholder.shares) === 0"
                                            class="ml-2 inline-flex items-center rounded-full bg-orange-100 px-2 py-0.5 text-xs font-semibold text-orange-700"
                                        >
                                            Cession
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ shareholder.shares_old }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ shareholder.rc }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button @click="
                                            handleEditDirector(shareholder)
                                            " :disabled="loading"
                                            :title="$t('cessionParts.edit')"
                                            class="text-indigo-600 disabled:bg-indigo-600/30 hover:text-indigo-900 mr-3">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button
                                            @click="handleDeleteShareholder(shareholder)"
                                            :disabled="loading"
                                            :title="$t('cessionParts.delete')"
                                            class="text-red-600 disabled:text-red-300 hover:text-red-800"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="aiProcessing" class="mt-4 text-yellow-600">
            <div class="text-center">
                {{ $t("cessionParts.txtinfo") }}<br> {{ $t("cessionParts.txtinfo2") }}
            </div>
            <div class="mt-4 mx-auto w-full max-w-md">
                <div class="flex items-center justify-between text-xs text-yellow-700 mb-1">
                    <span>Chargement</span>
                    <span>{{ Math.min(100, Math.round(aiProgress)) }}%</span>
                </div>
                <div class="h-3 w-full bg-yellow-100 rounded-full overflow-hidden">
                    <div
                        class="h-full bg-yellow-500 transition-all duration-200"
                        :style="{ width: `${Math.min(100, aiProgress)}%` }"
                    ></div>
                </div>
            </div>
        </div>
        <div class="buttons flex mt-6 justify-center">
            <button @click="prevStep()" type="button"
                class="mx-4 px-10 py-2 border rounded-full text-[#059669] border-[#059669] bg-white hover:bg-[#f2f2f2]">
                {{ $t('step1Validate.previousButton') }}
            </button>
            <button @click="continueHandle()" type="submit" :disabled="loading"
                class="mx-4 px-10 py-2 border rounded-full text-white bg-[#059669] disabled:bg-indigo-600/30">
                {{
                    loading
                        ? $t("cessionParts.loading")
                        : $t("cessionParts.continue")
                }}
            </button>
        </div>
        <div v-if="errorMessage"
            class="mt-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative"
            role="alert">
            <span class="block sm:inline">{{ errorMessage }}</span>
        </div>

    </div>
</template>

<script setup>
import { onMounted, inject, ref, watch, nextTick } from "vue";
import { useRouter } from "vue-router";
import { computed } from "vue";
// import path from "path-browserify";
import "../../openai/services/scrapePage.js";
// import fetch from "node-fetch";
// import { JSDOM } from "jsdom";
import axios from "axios";
import axiosInstance from "@/axios.js";
import { useI18n } from "vue-i18n";

const { t } = useI18n();
const updateStep = inject("updateStep");
const router = useRouter();
const loading = ref(false);
const aiProcessing = ref(false);
const shareholders = ref([]);
const total_shares = ref(0);
const errorMessage = ref("");
const amount = ref({
    device: "€",
    montant: 0,
});
const showAddForm = ref(false);
const showEditForm = ref(false);
const currentDirector = ref(null);
const originalSharesBeforeEdit = ref(null);
const newDirector = ref({ full_name: "", shares: "", shares_old: "", status: "", gsm: "" });
const contactType = ref("email");
const isEditing = ref([false, false]);
const errorMessageShares = ref("");
const verificationSent = ref(false);
const errorveriff = ref("");
const verificationLoadingShareholder = ref(false);
const verificationLoadingShareholderEdit = ref(false);
const newShareholderDocumentUrl = ref("");
const currentShareholderDocumentUrl = ref("");
const verificationMode = ref("manual"); // "manual" or "idanalyzer"
const verificationModeEdit = ref("manual"); // "manual" or "idanalyzer"

watch(verificationMode, () => {
    errorMessageShares.value = "";
});

const isBelgianPhoneValid = (phone) => {
    const regex = /^\+[1-9]\d{7,14}$/;
    return regex.test(phone);
};
const normalizeCurrency = (currency) => {
    if (!currency) return "€";
    if (typeof currency === "string") return currency;
    if (typeof currency === "object") {
        return currency.value || currency.code || currency.name || "€";
    }
    return "€";
};
const normalizeNumericInput = (value) => {
    if (value === null || value === undefined) return null;
    const trimmed = String(value).trim().replace(',', '.');
    if (trimmed === "") return null;
    const num = Number(trimmed);
    if (!Number.isFinite(num)) return null;
    return trimmed;
};
const count = ref([0, 0]);
const json = ref({});
const showChoiceModal = ref(true);
const aiProgress = ref(0);
let aiProgressTimer = null;
const AI_PROGRESS_DURATION_MS = 60000;
const AI_PROGRESS_TICK_MS = 200;

const startAiProgress = () => {
    aiProgress.value = 0;
    if (aiProgressTimer) {
        clearInterval(aiProgressTimer);
    }
    const step = 100 / (AI_PROGRESS_DURATION_MS / AI_PROGRESS_TICK_MS);
    aiProgressTimer = setInterval(() => {
        aiProgress.value = Math.min(100, aiProgress.value + step);
    }, AI_PROGRESS_TICK_MS);
};

const stopAiProgress = () => {
    if (aiProgressTimer) {
        clearInterval(aiProgressTimer);
        aiProgressTimer = null;
    }
    aiProgress.value = 100;
};

const aggregatedShareholders = computed(() => {
    const groups = new Map();

    shareholders.value.forEach((s) => {
        const raw = (s?.full_name || "").toLowerCase().trim();
        if (!raw) return;
        const tokens = raw.split(/\s+/).filter(Boolean).sort();
        const key = tokens.join(" ");
        if (!groups.has(key)) groups.set(key, []);
        groups.get(key).push(s);
    });

    const results = [];
    for (const [key, list] of groups.entries()) {
        const preferred = list.find((s) => s.is_origin === false) || list[0];
        const shares = preferred && preferred.shares != null ? parseFloat(String(preferred.shares).replace(',', '.')) : 0;
        const sharesOld = preferred && preferred.shares_old != null ? parseFloat(String(preferred.shares_old).replace(',', '.')) : 0;

        results.push({
            ...preferred,
            shares: Number.isFinite(shares) ? shares : 0,
            shares_old: Number.isFinite(sharesOld) ? sharesOld : 0,
            __ids: list.map((s) => s.id).filter((id) => id != null),
        });
    }

    return results;
});



const chooseManual = () => {
    showChoiceModal.value = false;
    showAddForm.value = true;
};

const chooseAI = async () => {
    showChoiceModal.value = false;
    aiProcessing.value = true;
    loading.value = true;
    startAiProgress();
    try {
        // Call the AI API to load shareholders
        sessionStorage.removeItem("openai_convert_unavailable");
        await getPersonnes({ forceAI: true });
    } finally {
        console.log("AI processing completed");
        stopAiProgress();
        loading.value = false;
        aiProcessing.value = false;
    }
};

// const loadShareholdersFromAI = async () => {
//     // You may want to show a loading spinner or message here
//     // This function should call your existing AI API logic
//     // Example: getShareholdersFromAI();
//     if (typeof getShareholdersFromAI === 'function') {
//         await getShareholdersFromAI();
//     } else if (typeof getShareholders === 'function') {
//         await getShareholders();
//     }
// };
// const directors = ref([]);
const startEditing = (index) => {
    isEditing.value[index] = true;
    count.value[index] = parseFloat(stats.value[index].count) || 0;
};

const submitEdit = async (index) => {
    try {
        const demandeId = sessionStorage.getItem("demande_id");

        // Clone current json safely
        const jsonData = json.value ? JSON.parse(JSON.stringify(json.value)) : {};
        if (!jsonData.capital || typeof jsonData.capital !== 'object') {
            jsonData.capital = {};
        }

        let payload = {};

        if (index === 0) {
            // Editing total shares only
            const newTotalShares = parseFloat(count.value[0]) || 0;
            stats.value[0].count = newTotalShares;
            total_shares.value = newTotalShares;

            // Do NOT change capital amount when editing shares total
            payload = {
                nb_total_actions: newTotalShares,
                montant: amount.value.montant, // keep current capital
            };
        } else if (index === 1) {
            // Editing capital amount only
            const newCapital = parseFloat(count.value[1]) || 0;
            stats.value[1].count = newCapital;
            amount.value.montant = newCapital;

            // Update json capital amount for consistency
            jsonData.capital.amount = newCapital;

            payload = {
                nb_total_actions: total_shares.value,
                montant: newCapital,
            };
        }

        console.log('updating demande with payload', payload);
        await axiosInstance.put(`/demandes/${demandeId}`, payload);

        // Close edit state
        isEditing.value[index] = false;
        json.value = jsonData;
    } catch (error) {
        console.error("Erreur lors de la mise à jour :", error);
    }
};

const saveCurrentEditIfNeeded = async () => {
    if (!showEditForm.value || !currentDirector.value) {
        return true;
    }

    if (!currentDirector.value.full_name || !currentDirector.value.shares) {
        errorMessageShares.value = t("cessionParts.errorFieldsNull");
        return false;
    }

    showEditForm.value = false;
    await updateDirigeant();
    return true;
};

const flushPendingEdits = async () => {
    const hasNewDirectorInput = () => {
        const current = newDirector.value || {};
        return Boolean(
            current.full_name ||
            current.shares ||
            current.shares_old ||
            current.rc ||
            current.gsm ||
            current.carte_identite
        );
    };

    const savePendingNewDirectorIfNeeded = async () => {
        if (!showAddForm.value || !hasNewDirectorInput()) {
            return true;
        }

        if (verificationMode.value === "idanalyzer") {
            return true;
        }

        if (!newDirector.value.full_name || !newDirector.value.shares) {
            errorMessageShares.value = t("cessionParts.errorFieldsNull");
            return false;
        }

        const confirmSave = window.confirm(t("cessionParts.unsavedConfirm"));
        if (!confirmSave) {
            return false;
        }

        showAddForm.value = false;
        await addNewDirigeant();
        return true;
    };

    const savedNew = await savePendingNewDirectorIfNeeded();
    if (!savedNew) {
        return false;
    }

    const pendingIndexes = isEditing.value
        .map((editing, index) => (editing ? index : null))
        .filter((index) => index !== null);

    for (const index of pendingIndexes) {
        await submitEdit(index);
    }

    return await saveCurrentEditIfNeeded();
};

const prevStep = async () => {
    loading.value = true;
    const saved = await flushPendingEdits();
    if (!saved) {
        loading.value = false;
        return;
    }
    const demande = await getDemandeById();
    if (demande == null) {
        loading.value = false;
        return;
    }
    const address_service = demande.demande.address_service;
    const dirigeants_service = demande.demande.dirigeants_service;
    if (dirigeants_service == 1) {
        router.push({ name: "dirigeantsService" });
    } else if (address_service == 1) {
        router.push({ name: "addressService" });
    }
    else {
        router.push({ name: "step3" });
    }
    loading.value = false;
};


const stats = ref([
    {
        title: "cessionParts.totalSharesTitle",
        count: total_shares.value,
        icon: "fas fa-square-up-right",
        bgColor: "#059669",
        device: "",
    },
    {
        title: "cessionParts.capitalAmountTitle",
        count: amount.value.montant,
        device: amount.value.device,
        icon: "fas fa-money-bills",
        bgColor: "#059669",
    },
]);

const initializeFromExistingDemande = async () => {
    const existing = await getDemande();
    if (!existing) {
        return;
    }

    try {
        if (existing.bce_data) {
            json.value = JSON.parse(existing.bce_data);
        }
    } catch {
        json.value = {};
    }

    if (Array.isArray(existing.shareholders) && existing.shareholders.length > 0) {
        shareholders.value = existing.shareholders;
    }

    if (existing.nb_total_actions != null) {
        const parsed = parseFloat(String(existing.nb_total_actions).replace(",", "."));
        if (Number.isFinite(parsed)) {
            total_shares.value = parsed;
        }
    } else if (!total_shares.value && shareholders.value.length > 0) {
        const sumShares = shareholders.value.reduce((sumAcc, s) => {
            const v = s && s.shares != null ? parseFloat(String(s.shares).replace(",", ".")) : 0;
            return sumAcc + (Number.isFinite(v) ? v : 0);
        }, 0);
        total_shares.value = sumShares;
    }

    if (existing.montant != null && existing.montant !== "") {
        const parsedAmount = parseFloat(String(existing.montant).replace(",", "."));
        if (Number.isFinite(parsedAmount)) {
            amount.value.montant = parsedAmount;
        }
    } else {
        const capital = json.value && json.value.capital ? json.value.capital : null;
        if (capital) {
            const rawAmount = capital.amount ?? capital.total_capital ?? capital.montant;
            if (rawAmount != null) {
                const parsedAmount = parseFloat(String(rawAmount).replace(",", "."));
                if (Number.isFinite(parsedAmount)) {
                    amount.value.montant = parsedAmount;
                }
            }
            if (capital.currency) {
                amount.value.device = normalizeCurrency(capital.currency);
            }
        }
    }

    if (shareholders.value.length > 0) {
        showChoiceModal.value = false;
    } else {
        showChoiceModal.value = true;
    }
};

onMounted(async () => {
    nextTick(() => {
        showChoiceModal.value = true;
    });
    await checkCreditsAndRedirect();
    await initializeFromExistingDemande();
    if (updateStep) updateStep(7);
});

const continueHandle = async () => {
    loading.value = true;
    const saved = await flushPendingEdits();
    if (!saved) {
        loading.value = false;
        return;
    }
    if (!aggregatedShareholders.value || aggregatedShareholders.value.length === 0) {
        errorMessage.value = t("cessionParts.errorShareholdersMissing") || "Veuillez récupérer les actionnaires via l’API avant de continuer.";
        loading.value = false;
        return;
    }
    const capitalValue = normalizeNumericInput(amount.value.montant);
    if (capitalValue === null || Number(capitalValue) <= 0) {
        errorMessage.value = t("cessionParts.errorCapitalRequired");
        loading.value = false;
        return;
    }
    const hasMissingRc = aggregatedShareholders.value.some((shareholder) => {
        const rc = shareholder?.rc;
        return !rc || String(rc).trim() === "";
    });
    if (hasMissingRc) {
        errorMessage.value = t("cessionParts.errorRcRequired");
        loading.value = false;
        return;
    }
    //ajouter controle sur les parts des actionnaire avant  de passer à l'étape suivante
    // compute numeric sum of shares (parse strings safely)
    const sum = aggregatedShareholders.value.reduce((sumAcc, s) => {
        const v = s && s.shares != null ? parseFloat(String(s.shares).replace(',', '.')) : 0;
        return sumAcc + (Number.isFinite(v) ? v : 0);
    }, 0);

    // expected total (may be string) -> parse to number
    const expected = total_shares.value != null ? parseFloat(String(total_shares.value).replace(',', '.')) : 0;

    console.log('shares sum vs expected', sum, expected, shareholders.value);

    // allow a small tolerance for floating point sums
    const tolerance = 0.0001;
    if (Math.abs(sum - expected) > tolerance) {
        errorMessage.value = t("cessionParts.errorSharesMessage");
        loading.value = false;
        return;
    }

    // clear any previous error and proceed
    errorMessage.value = "";
    router.push({ name: "step4" });
    loading.value = false;
};
const getDirigeants = async () => {
    loading.value = true;
    const demande_id = sessionStorage.getItem("demande_id") || 0;
    const response = await axiosInstance
        .get(`demandes/${demande_id}`)
        .then(({ data }) => {
            if (data && data.demande.dirigeants) {
                shareholders.value = data.demande.shareholders;
                console.log(data);
            }
        })
        .catch((error) => error)
        .finally(() => (loading.value = false));
};

// Gestion de l'ajout d'un nouveau dirigeant
const handleAddDirector = () => {
    if (verificationMode.value === "idanalyzer") {
        errorMessageShares.value = "";
        sendIdentificationShareholder();
        return;
    }

    if (newDirector.value.full_name && newDirector.value.shares) {
        const directorToAdd = {
            rc: null,
            full_name: newDirector.value.full_name,
            shares: newDirector.value.shares,
            shares_old: newDirector.value.shares_old,
        };
        currentDirector.value = directorToAdd;
        showAddForm.value = false;
        addNewDirigeant();
    }
    else {
        errorMessageShares.value = t("cessionParts.errorFieldsNull");
        return;
    }
};

// Gestion de la modification d'un dirigeant
const handleEditDirector = (director) => {
    const base = { ...director, gsm: director.gsm || "" };
    if (director.__ids && director.__ids.length > 0) {
        base.id = director.__ids[0];
    }
    currentDirector.value = base;
    originalSharesBeforeEdit.value = director.shares ?? null;
    showEditForm.value = true;
    showAddForm.value = false;
};

const handleDeleteShareholder = async (shareholder) => {
    if (!shareholder) return;
    const ids = Array.isArray(shareholder.__ids) && shareholder.__ids.length > 0
        ? shareholder.__ids
        : (shareholder.id ? [shareholder.id] : []);

    if (ids.length === 0) return;

    if (!window.confirm(t("cessionParts.deleteConfirm"))) {
        return;
    }

    loading.value = true;
    try {
        await Promise.all(ids.map((id) => axiosInstance.delete(`shareholders/${id}`)));
        shareholders.value = shareholders.value.filter((s) => !ids.includes(s.id));
    } catch (error) {
        console.error("Erreur suppression shareholder :", error);
        errorMessage.value = t("cessionParts.deleteError") || "Erreur lors de la suppression.";
    } finally {
        loading.value = false;
    }
};

// Sauvegarde des modifications
const handleSaveEdit = () => {
    if (currentDirector.value) {
        // gives us the indes of the dirctor on directors array
        const index = shareholders.value.findIndex(
            (d) => d.id === currentDirector.value.id
        );
        console.log(index);
        if (index !== -1) {
            shareholders.value.splice(index, 1, { ...currentDirector.value });
        }
        //tester si les champs sont bien remplis
        if (!currentDirector.value.full_name || !currentDirector.value.shares) {
            errorMessageShares.value = t("cessionParts.errorFieldsNull");
            return;
        }
        showEditForm.value = false;
        updateDirigeant();
    }

};


// Fermeture des formulaires
const handleCancel = () => {
    showAddForm.value = false;
    showEditForm.value = false;
    newDirector.full_name = "";
    newDirector.rc = "";
    newDirector.shares = "";
    newDirector.gsm = "";
    currentDirector.value = null;
};

// add new dirigeant

const addNewDirigeant = async () => {
    loading.value = true;

    if (!newDirector.value.full_name || !newDirector.value.shares) {
        loading.value = false;
        return;
    }

    try {
        const formData = new FormData();

        // champs globaux
        formData.append(
            "demande_id",
            sessionStorage.getItem("demande_id")
        );
        formData.append("is_origin", 0);
        formData.append("total_shares", total_shares.value);

        // shareholder[0]
        formData.append(
            "shareholders[0][full_name]",
            newDirector.value.full_name
        );
        formData.append(
            "shareholders[0][shares]",
            newDirector.value.shares
        );
        formData.append(
            "shareholders[0][shares_old]",
            newDirector.value.shares_old ?? ""
        );
        formData.append(
            "shareholders[0][rc]",
            newDirector.value.rc ?? ""
        );
        formData.append(
            "shareholders[0][gsm]",
            newDirector.value.gsm ?? ""
        );

        if (newDirector.value.carte_identite instanceof File) {
            formData.append(
                "shareholders[0][carte_identite]",
                newDirector.value.carte_identite
            );
        }

        // appel direct du store
        const resp = await axiosInstance.post(
            "shareholders",
            formData,
            {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            }
        );

        const updatedShareholders = resp.data;

        // MAJ liste complète renvoyée par le backend
        shareholders.value = updatedShareholders;

        // reset formulaire
        newDirector.value = {
            full_name: "",
            shares: "",
            shares_old: "",
            rc: "",
            gsm: "",
            carte_identite: null,
        };
        currentDirector.value = null;

    } catch (error) {
        console.error("Erreur ajout shareholder :", error);
        errorMessage.value =
            error.response?.data?.error ||
            "Erreur lors de l'ajout du shareholder";
    } finally {
        loading.value = false;
    }
};

const updateDirigeant = async () => {
    if (
        !currentDirector.value ||
        !currentDirector.value.full_name ||
        !currentDirector.value.shares
    ) {
        errorMessage.value = "Full name and shares are required.";
        return;
    }

    const sharesValue = normalizeNumericInput(currentDirector.value.shares);
    if (sharesValue === null) {
        errorMessage.value = t("cessionParts.errorFieldsNull");
        return;
    }
    let sharesOldValue = normalizeNumericInput(currentDirector.value.shares_old);
    if (sharesOldValue === null) {
        const fallbackOld = normalizeNumericInput(originalSharesBeforeEdit.value);
        if (fallbackOld !== null) {
            sharesOldValue = fallbackOld;
            currentDirector.value.shares_old = fallbackOld;
        }
    }

    const formData = new FormData();
    formData.append('full_name', currentDirector.value.full_name);
    formData.append('shares', sharesValue);
    if (sharesOldValue !== null) {
        formData.append('shares_old', sharesOldValue);
    }
    formData.append('rc', currentDirector.value.rc ?? '');
    formData.append('gsm', currentDirector.value.gsm ?? '');

    if (currentDirector.value.carte_identite instanceof File) {
        formData.append(
            'carte_identite',
            currentDirector.value.carte_identite
        );
    }

    try {
        const response = await axiosInstance.post(
            `shareholders/${currentDirector.value.id}?_method=PUT`,
            formData,
            {
                headers: { 'Content-Type': 'multipart/form-data' }
            }
        );

        if (response.data) {
            const index = shareholders.value.findIndex(
                (d) => d.id === currentDirector.value.id
            );
            if (index !== -1) {
                shareholders.value[index] = response.data;
            }
            errorMessage.value = "";
        }
    } catch (error) {
        errorMessage.value =
            error.response?.data?.error ||
            "An error occurred while updating the shareholder";
        console.error(error);
    }

    // reset uniquement currentDirector
    setTimeout(() => {
        currentDirector.value = {
            full_name: '',
            shares: '',
            shares_old: '',
            rc: '',
            gsm: '',
            carte_identite: null
        };
    }, 2000);
};

const sendIdentificationShareholder = async () => {
    errorveriff.value = "";
    errorMessageShares.value = "";
    verificationSent.value = false;
    verificationLoadingShareholder.value = true;

    if (!newDirector.value.gsm || !isBelgianPhoneValid(newDirector.value.gsm)) {
        errorveriff.value = t("step2.errorInvalidPhoneFormat");
        verificationLoadingShareholder.value = false;
        return;
    }

    try {
        // If newDirector hasn't been saved yet, save it first to get the ID
        let shareholderId = newDirector.value.id;
        const canCreateShareholder = Boolean(newDirector.value.full_name && newDirector.value.shares);

        if (!shareholderId && canCreateShareholder) {
            console.log("📝 Saving new shareholder first...");
            // Save the shareholder first
            const data = new FormData();
            data.append('demande_id', sessionStorage.getItem("demande_id"));
            data.append('is_origin', 0);
            data.append('total_shares', total_shares.value);
            data.append('shareholders[0][full_name]', newDirector.value.full_name);
            data.append('shareholders[0][shares]', newDirector.value.shares);
            data.append('shareholders[0][shares_old]', newDirector.value.shares_old ?? '');
            data.append('shareholders[0][rc]', newDirector.value.rc ?? '');
            data.append('shareholders[0][gsm]', newDirector.value.gsm ?? '');
            if (newDirector.value.carte_identite instanceof File) {
                data.append('shareholders[0][carte_identite]', newDirector.value.carte_identite);
            }

            const response = await axiosInstance.post('shareholders', data, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            const createdShareholders = response.data;
            if (createdShareholders && createdShareholders.length > 0) {
                shareholderId = createdShareholders[0].id;
                newDirector.value.id = shareholderId;
                console.log("✅ Shareholder saved with ID:", shareholderId);
            }
        }

        const payload = {
            GSM: newDirector.value.gsm,
            demande_id: sessionStorage.getItem("demande_id"),
        };
        if (shareholderId) {
            payload.shareholder_id = shareholderId;
            console.log("📤 Sending verification for shareholder:", shareholderId);
        } else {
            console.log("📤 Sending verification without shareholder_id");
        }

        const verResponse = await axiosInstance.post("/verifications", payload);
        console.log("✅ Verification created:", verResponse.data);
        verificationSent.value = true;
    } catch (e) {
        console.error("Identification failed", e);
        errorveriff.value = t("step2.errorServer");
    } finally {
        verificationLoadingShareholder.value = false;
    }
};

const sendIdentificationShareholderEdit = async () => {
    errorveriff.value = "";
    verificationSent.value = false;
    verificationLoadingShareholderEdit.value = true;

    if (!currentDirector.value?.gsm || !isBelgianPhoneValid(currentDirector.value.gsm)) {
        errorveriff.value = t("step2.errorInvalidPhoneFormat");
        verificationLoadingShareholderEdit.value = false;
        return;
    }

    try {
        const payload = {
            GSM: currentDirector.value.gsm,
            demande_id: sessionStorage.getItem("demande_id")
        };
        
        // If editing an existing shareholder, include its ID
        if (currentDirector.value.id) {
            payload.shareholder_id = currentDirector.value.id;
            console.log("📤 Sending verification for shareholder:", currentDirector.value.id);
        }
        
        const verResponse = await axiosInstance.post("/verifications", payload);
        console.log("✅ Verification created:", verResponse.data);
        verificationSent.value = true;
    } catch (e) {
        console.error("Identification failed", e);
        errorveriff.value = t("step2.errorServer");
    } finally {
        verificationLoadingShareholderEdit.value = false;
    }
};


const getDemande = async () => {
    const demande_id = sessionStorage.getItem("demande_id") || 0;
    try {
        const response = await axiosInstance.get(
            `demandes/${demande_id}/shareholders`
        );
        return response.data;
    } catch (error) {
        console.error("Error fetching address:", error);
        return null;
    }
};

const getPersonnes = async (options = {}) => {
    const { forceAI = false } = options;
    const activeRequest = await getDemande();
    if (!activeRequest) {
        return;
    }
    console.log(activeRequest);
    const enterprise_number = activeRequest.enterprise_number;

    if (!json.value || Object.keys(json.value).length === 0) {
        try {
            if (activeRequest.bce_data) {
                json.value = JSON.parse(activeRequest.bce_data);
            } else {
                json.value = {};
            }
        } catch {
            json.value = {};
        }
    }

    const capitalFromJson = json.value && json.value.capital ? json.value.capital : null;
    const hasExistingShares = Array.isArray(activeRequest.shareholders) && activeRequest.shareholders.length > 0;
    const hasExistingTotals = activeRequest.nb_total_actions != null || (capitalFromJson && capitalFromJson.total_actions != null);

    if (!forceAI && (hasExistingShares || hasExistingTotals)) {
        if (activeRequest.nb_total_actions != null) {
            const parsedShares = parseFloat(String(activeRequest.nb_total_actions).replace(",", "."));
            if (Number.isFinite(parsedShares)) {
                total_shares.value = parsedShares;
            }
        } else if (capitalFromJson && capitalFromJson.total_actions != null) {
            const parsedShares = parseFloat(String(capitalFromJson.total_actions).replace(",", "."));
            if (Number.isFinite(parsedShares)) {
                total_shares.value = parsedShares;
            }
        }

        if (activeRequest.montant != null && activeRequest.montant !== "") {
            const parsedAmount = parseFloat(String(activeRequest.montant).replace(",", "."));
            if (Number.isFinite(parsedAmount)) {
                amount.value.montant = parsedAmount;
            }
        } else if (capitalFromJson && capitalFromJson.amount != null) {
            amount.value.montant = capitalFromJson.amount;
            amount.value.device = normalizeCurrency(capitalFromJson.currency);
        } else if (capitalFromJson && capitalFromJson.currency && !amount.value.device) {
            amount.value.device = normalizeCurrency(capitalFromJson.currency);
        }

        if (hasExistingShares) {
            shareholders.value = activeRequest.shareholders;
        }

        return;
    }

    let aiData = null;
    let aiUnavailable = false;
    const convertUnavailable = !forceAI && sessionStorage.getItem("openai_convert_unavailable") === "1";
    try {
        if (!convertUnavailable) {
            const { data } = await axiosInstance.get(
                `openai/convert/${enterprise_number}`,
                {
                    timeout: 0,
                }
            );
            if (data && data.finalResponse && data.finalResponse.finalResponse) {
                aiData = data.finalResponse.finalResponse;
            }
        }
    } catch (error) {
        if (error?.response?.status === 404) {
            sessionStorage.setItem("openai_convert_unavailable", "1");
        }
        aiUnavailable = true;
        console.log(error);
    }
    if (!aiData && !convertUnavailable) {
        aiUnavailable = true;
    }
    if (capitalFromJson && capitalFromJson.amount != null) {
        amount.value.montant = capitalFromJson.amount;
        amount.value.device = normalizeCurrency(capitalFromJson.currency);
    } else if (aiData && aiData.total_capital != null) {
        amount.value.montant = aiData.total_capital;
        amount.value.device = normalizeCurrency(capitalFromJson && capitalFromJson.currency ? capitalFromJson.currency : null);
    } else if (capitalFromJson && capitalFromJson.currency && !amount.value.device) {
        amount.value.device = normalizeCurrency(capitalFromJson.currency);
    }

    if (aiData && Array.isArray(aiData.shareholders) && aiData.total_shares != null) {
        total_shares.value = aiData.total_shares;
        await addShareHolders(
            aiData.shareholders,
            true,
            aiData.total_shares
        );
    } else {
        if (activeRequest.nb_total_actions != null) {
            const parsedShares = parseFloat(String(activeRequest.nb_total_actions).replace(",", "."));
            if (Number.isFinite(parsedShares)) {
                total_shares.value = parsedShares;
            }
        } else if (capitalFromJson && capitalFromJson.total_actions != null) {
            const parsedShares = parseFloat(String(capitalFromJson.total_actions).replace(",", "."));
            if (Number.isFinite(parsedShares)) {
                total_shares.value = parsedShares;
            }
        }

        if (Array.isArray(activeRequest.shareholders) && activeRequest.shareholders.length > 0) {
            shareholders.value = activeRequest.shareholders;
        }

        if ((aiUnavailable || convertUnavailable) && shareholders.value.length === 0) {
            showAddForm.value = true;
        }
    }
};

const addShareHolders = async (
    shareholdersParam,
    is_origin = false,
    nb_total_actions
) => {
    const body = {
        demande_id: sessionStorage.getItem("demande_id"),
        shareholders: shareholdersParam,
        is_origin: is_origin,
        total_shares: nb_total_actions,
    };
    try {
        const resp = await axiosInstance.post("shareholders", body);
        const data = resp.data;
        if (data) {
            shareholders.value = data;
            errorMessage.value = "";
            // verificationSent.value = true;
        }
        return data;
    } catch (error) {
        if (error.response && error.response.data) {
            errorMessage.value = error.response.data.error;
        } else {
            errorMessage.value = "An error occurred while adding shareholders";
        }
        console.error(error);
        return null;
    }
};
watch(
    total_shares,
    (newCount) => {
        stats.value = [
            {
                title: "cessionParts.totalSharesTitle",
                count: newCount,
                icon: "fas fa-square-up-right",
                bgColor: "#059669",
                device: "",
            },
            {
                title: "cessionParts.capitalAmountTitle",
                count: amount.value.montant,
                device: amount.value.device,
                icon: "fas fa-money-bills",
                bgColor: "#059669",
            },
        ];
    },
    { deep: true }
);
watch(
    amount,
    (newCount) => {
        stats.value = [
            {
                title: t("cessionParts.totalActions"),
                count: total_shares.value,
                icon: "fas fa-square-up-right",
                bgColor: "#059669",
                device: "",
            },
            {
                title: t("cessionParts.amount"),
                count: newCount.montant,
                device: newCount.device,
                icon: "fas fa-money-bills",
                bgColor: "#059669",
            },
        ];
    },
    { deep: true }
);
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
const getDemandeById = async () => {
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
</script>
<style scoped>
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
}

.stat-icon i {
    color: white;
    font-size: 1.5rem;
}

.stat-info h3 {
    margin: 0;
    color: #64748b;
    font-size: 0.875rem;
    font-weight: 500;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1e293b;
}

@media (max-width: 768px) {
    .stats-container {
        grid-template-columns: 1fr;
    }
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.animate-fade-in {
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    0% {
        opacity: 0;
        transform: scale(0.95);
    }

    100% {
        opacity: 1;
        transform: scale(1);
    }
}
</style>
