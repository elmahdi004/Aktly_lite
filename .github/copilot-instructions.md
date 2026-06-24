<!-- Copilot instructions for AI coding agents -->
# Copilot instructions — Aktly

Purpose
- Help AI agents be immediately productive in this Laravel 11 + Vue 3 (Vite) app.

Big picture (architecture & data flow)
- Backend is Laravel: controllers in app/Http/Controllers call domain/integration services in app/Services; persistence in app/Models and database/migrations.
- Frontend is Vue 3 + Pinia + Vue Router + vue-i18n in resources/js, bundled via Vite. Entry is resources/js/app.js; i18n locale is set from user lang (auth store) and stored in localStorage as lang.
- Core workflow centers on Demande: routes/api.php creates/updates Demande; OpenAIController builds structured donnees_acte via OpenAIService and generates PV/extrait text with strict “no markdown / no invented data” prompts.

Key files to read first
- app/Services/OpenAIService.php: OpenAI Assistants + chat integration (Guzzle), prompt construction, donnees_acte assembly, improve-text prompt rules.
- app/Http/Controllers/OpenAIController.php: PV/extrait + improve-text endpoints (Gemini key only used for presence checks; OpenAIService does the work).
- app/Http/Middleware/CheckCredits.php: credits middleware with resume shortcut when demande_id belongs to the user.
- routes/api.php: Sanctum-protected routes, credits middleware on POST /demandes, OpenAI endpoints, Veriff/Stripe/Nominatim proxies.
- resources/js/axios.js: API base URL from VITE_API_BASE_URL (default https://form.legakte.be/api), Bearer token attachment, 401→logout redirect, 402→/buy-credits.
- config/services.php and .env.example: API keys/models (OpenAI/Gemini/Stripe/Twilio/Veriff/Nominatim, OPENAI_CONVERT_URLS, BNB API).

Critical workflows (repo scripts)
- Full dev stack: composer run dev (serve + queue:listen + pail + Vite via concurrently).
- Frontend only: npm run dev. Build: npm run build.
- Optional Node helper: npm run start runs resources/openai/server.js (PDF→text experiments).
- Tests: php artisan test (phpunit.xml).

Project-specific conventions
- Put external integrations and business logic in app/Services/*; controllers stay thin.
- Logging uses \Log::info()/\Log::error() with structured context arrays; keep keys stable for log parsing.
- Credits enforcement: use CheckCredits middleware with optional cost param; resume operations bypass credit spending when demande_id belongs to user.
- ShortLinkService::store() uses code format xxx-xxx-xxx; keep format stable.
- Ignore tmp_deploy_* directories when editing source.

Integrations & external deps (examples)
- OpenAI is the active LLM backend: services.openai.* → OPENAI_API_KEY/OPENAI_ASSISTANT_ID (used by OpenAIService).
- Gemini config exists in config/services.php and is only used for key presence checks in OpenAIController.
- Stripe checkout, Twilio SMS, Veriff identity, and Nominatim geocoding are wired via controllers/services and config/services.php.
- OpenAI PDF conversion is proxied by OpenAIProxyController using OPENAI_CONVERT_URLS.

When you change things
- If you touch prompt templates in OpenAIService, preserve “no markdown / no invented data” constraints and legal formatting.
- If you add env keys, update both config/services.php and .env.example.

Feedback
- Tell me which sections are unclear or missing so I can refine this.
