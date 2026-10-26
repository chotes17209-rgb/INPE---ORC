<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente para servicios de IA compatibles con la API "chat completions"
 * de OpenAI (OpenAI, Groq u otros). El proveedor se define en config/services.php.
 */
class OpenAIService
{
    private ?string $apiKey;
    private string $model;
    private string $baseUrl;
    private int $timeout;

    public function __construct()
    {
        $this->apiKey  = config('services.ia.api_key');
        $this->model   = config('services.ia.model');
        $this->baseUrl = rtrim(config('services.ia.base_url'), '/');
        $this->timeout = config('services.ia.timeout', 60);
    }

    public function estaConfigurado(): bool
    {
        return filled($this->apiKey) && ! str_contains((string) $this->apiKey, 'your-');
    }

    public function modelo(): string
    {
        return $this->model;
    }

    public function consultar(string $mensaje, string $systemPrompt = ''): string
    {
        if (! $this->estaConfigurado()) {
            return 'El servicio de inteligencia artificial no está configurado. '
                . 'Registre la clave de acceso (IA_API_KEY) en el archivo .env.';
        }

        $messages = [];
        if ($systemPrompt !== '') {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $mensaje];

        try {
            $response = Http::withToken($this->apiKey)
                ->acceptJson()
                ->timeout($this->timeout)
                ->post("{$this->baseUrl}/chat/completions", [
                    'model'       => $this->model,
                    'messages'    => $messages,
                    'temperature' => 0.3,
                ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content') ?: 'El servicio de IA no devolvió una respuesta.';
            }

            Log::error('Servicio de IA: respuesta con error', ['status' => $response->status(), 'body' => $response->body()]);

            return 'No fue posible obtener una respuesta del servicio de IA (código ' . $response->status() . '). '
                . 'Verifique la configuración e intente nuevamente.';
        } catch (\Throwable $e) {
            Log::error('Servicio de IA: ' . $e->getMessage());

            return 'El servicio de IA no se encuentra disponible en este momento. Intente nuevamente más tarde.';
        }
    }
}
