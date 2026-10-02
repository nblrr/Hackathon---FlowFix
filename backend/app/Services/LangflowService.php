<?php
namespace App\Services;
use App\Data\AiOutput;
use App\Exceptions\ApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
class LangflowService {
    /**
     * Run a Langflow flow.
     *
     * All four FlowFix flows use a single ChatInput node (id="ci-main").
     * The mapping for each flow is ['input' => 'ci-main'].
     * We combine all $inputs into one JSON string and pass it as the input_value tweak.
     */
    public function run(string $flow, array $inputs, string $session): array {
        $config = config('flowfix.langflow');
        $id = $config['flows'][$flow] ?? null;
        if (!$id || !$config['api_key']) {
            throw new ApiException('AI_NOT_CONFIGURED', 'Layanan AI belum dikonfigurasi. Data Anda tetap tersimpan; lengkapi data atau minta pemeriksaan manusia.', 503);
        }
        if (mb_strlen(json_encode($inputs)) > config('flowfix.max_context_chars')) {
            throw new ApiException('DOCUMENT_CONTEXT_TOO_LARGE', 'Konteks dokumen melampaui batas analisis. Tidak ada halaman yang dipotong. Minta pemeriksaan manusia.', 422);
        }

        $mapping = $config['mapping'][$flow] ?? [];

        // Single-input pattern: all flows map 'input' => 'ci-main'.
        // Combine all $inputs keys into one JSON payload sent to the single ChatInput.
        if (count($mapping) === 1 && isset($mapping['input'])) {
            $componentId = $mapping['input'];
            $combined = count($inputs) === 1
                ? (is_string(reset($inputs)) ? reset($inputs) : json_encode(reset($inputs), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))
                : json_encode($inputs, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $tweaks = [$componentId => ['input_value' => $combined]];
        } else {
            // Legacy multi-input fallback (kept for compatibility)
            $tweaks = [];
            foreach ($mapping as $key => $component) {
                if (!array_key_exists($key, $inputs)) {
                    throw new \LogicException("Missing flow input: $key");
                }
                $tweaks[$component] = ['input_value' => is_string($inputs[$key])
                    ? $inputs[$key]
                    : json_encode($inputs[$key], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)];
            }
        }

        try {
            $response = Http::withHeaders(['x-api-key' => $config['api_key']])
                ->acceptJson()
                ->connectTimeout($config['connect_timeout'])
                ->timeout($config['run_timeout'])
                ->post(
                    rtrim($config['base_url'], '/') . '/api/v1/run/' . rawurlencode($id),
                    ['input_type' => 'chat', 'output_type' => 'chat', 'session_id' => $session, 'tweaks' => $tweaks]
                );
        } catch (ConnectionException) {
            throw new ApiException('AI_TIMEOUT', 'Layanan AI tidak terhubung atau melewati batas waktu. Ini bukan temuan kekurangan berkas. Coba lagi.', 504);
        }

        if (!$response->successful()) {
            Log::warning('Langflow run failed', ['flow' => $flow, 'http_status' => $response->status(), 'body' => $response->body()]);
            throw new ApiException('AI_UPSTREAM_ERROR', 'Langflow belum dapat menyelesaikan analisis. Periksa konfigurasi flow dan model; data Anda tetap tersimpan.', 502);
        }

        $envelope = $response->json();
        $text = data_get($envelope, 'outputs.0.outputs.0.results.message.text');
        if (!is_string($text)) throw AiOutput::invalid();
        return AiOutput::parse($flow, $text)->value;
    }
}
