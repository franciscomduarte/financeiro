<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelarAgendamentoRequest;
use App\Http\Requests\ReagendarAgendamentoRequest;
use App\Http\Requests\StoreAgendamentoRequest;
use App\Http\Resources\AgendamentoResource;
use App\Models\Agendamento;
use App\Services\AgendamentoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use RuntimeException;
use Throwable;

class AgendamentoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $agendamentos = Agendamento::with(['paciente:id,nome,telefone', 'profissional:id,nome', 'procedimento:id,nome,duracao_minutos'])
            ->when($request->input('profissional_id'), fn ($q, $v) => $q->where('profissional_id', $v))
            ->when($request->input('paciente_id'), fn ($q, $v) => $q->where('paciente_id', $v))
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->input('data_inicio'), fn ($q, $v) => $q->whereDate('inicio_em', '>=', $v))
            ->when($request->input('data_fim'), fn ($q, $v) => $q->whereDate('inicio_em', '<=', $v))
            ->orderBy('inicio_em')
            ->paginate(20);

        return AgendamentoResource::collection($agendamentos);
    }

    public function show(Agendamento $agendamento): AgendamentoResource
    {
        $agendamento->load(['paciente', 'profissional', 'procedimento', 'agendamentoOrigem']);

        return new AgendamentoResource($agendamento);
    }

    public function store(StoreAgendamentoRequest $request, AgendamentoService $service): AgendamentoResource
    {
        $agendamento = $service->criar($request->validated());

        return new AgendamentoResource($agendamento);
    }

    public function cancelar(
        Agendamento $agendamento,
        CancelarAgendamentoRequest $request,
        AgendamentoService $service,
    ): AgendamentoResource|JsonResponse {
        try {
            $agendamento = $service->cancelar($agendamento, $request->validated('motivo_cancelamento'));
            return new AgendamentoResource($agendamento);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function reagendar(
        Agendamento $agendamento,
        ReagendarAgendamentoRequest $request,
        AgendamentoService $service,
    ): AgendamentoResource|JsonResponse {
        try {
            $novo = $service->reagendar(
                $agendamento,
                $request->validated('nova_data'),
                $request->validated('novo_horario'),
            );
            return new AgendamentoResource($novo);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function realizado(Agendamento $agendamento, AgendamentoService $service): AgendamentoResource
    {
        return new AgendamentoResource($service->marcarRealizado($agendamento));
    }

    public function falta(Agendamento $agendamento, AgendamentoService $service): AgendamentoResource
    {
        return new AgendamentoResource($service->marcarFalta($agendamento));
    }

    public function slots(Request $request, AgendamentoService $service): JsonResponse
    {
        $request->validate([
            'profissional_id' => ['required', 'uuid', 'exists:profissionais,id'],
            'data'            => ['required', 'date_format:Y-m-d'],
            'duracao'         => ['nullable', 'integer', 'min:15', 'max:480'],
        ]);

        $duracao = (int) $request->input('duracao', 60);
        $slots   = $service->slotsDisponiveis(
            $request->input('profissional_id'),
            $request->input('data'),
            $duracao,
        );

        return response()->json(['slots' => $slots]);
    }
}
