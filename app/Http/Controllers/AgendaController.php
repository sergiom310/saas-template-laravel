<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\FranjaHoraria;
use App\Models\Profesional;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AgendaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Agenda::with(['franjaHoraria', 'profesional', 'cliente']);

            // Filtrar por fecha si se proporciona
            if ($request->has('fecha')) {
                $query->whereDate('fecha', $request->fecha);
            }

            // Filtrar por profesional si se proporciona
            if ($request->has('id_profesional')) {
                $query->where('id_profesional', $request->id_profesional);
            }

            // Filtrar por estado si se proporciona
            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            $response = $query->orderBy('fecha', 'desc')
                             ->orderBy('id_franja')
                             ->get();

            return response()->json($response, 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Get disponibilidad para una fecha y profesional específico
     */
    public function disponibilidad(Request $request): JsonResponse
    {
        try {
            $fecha = $request->input('fecha');
            $idProfesional = $request->input('id_profesional');

            // Obtener todas las franjas horarias
            $franjas = FranjaHoraria::orderBy('hora_inicio')->get();

            // Obtener las agendas ocupadas para esa fecha y profesional con toda la información
            $agendasOcupadas = Agenda::with(['cliente', 'profesional', 'franjaHoraria'])
                ->where('fecha', $fecha)
                ->where('id_profesional', $idProfesional)
                ->whereIn('estado', ['reservado', 'atendido'])
                ->get()
                ->keyBy('id_franja');

            // Marcar franjas disponibles y agregar información de la agenda si está ocupada
            $disponibilidad = $franjas->map(function ($franja) use ($agendasOcupadas) {
                $agenda = $agendasOcupadas->get($franja->id_franja);

                return [
                    'id_franja' => $franja->id_franja,
                    'hora_inicio' => $franja->hora_inicio,
                    'hora_fin' => $franja->hora_fin,
                    'disponible' => !$agenda,
                    'agenda' => $agenda ? [
                        'id_agenda' => $agenda->id_agenda,
                        'id_cliente' => $agenda->id_cliente,
                        'cliente' => $agenda->cliente ? $agenda->cliente->nombre : 'N/A',
                        'procedimiento' => $agenda->procedimiento ?: 'Sin especificar',
                        'estado' => $agenda->estado,
                        'fecha_creacion' => $agenda->fecha_creacion
                    ] : null
                ];
            });

            return response()->json($disponibilidad, 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validar
            $validated = $request->validate([
                'fecha' => 'required|date',
                'id_franja' => 'required|integer',
                'id_profesional' => 'required|integer',
                'id_cliente' => 'required|integer',
                'procedimiento' => 'nullable|string',
                'estado' => 'nullable|in:reservado,atendido,cancelado',
            ]);

            // Verificar si existe una reserva (activa o cancelada)
            $reservaExistente = Agenda::where('fecha', $validated['fecha'])
                ->where('id_franja', $validated['id_franja'])
                ->where('id_profesional', $validated['id_profesional'])
                ->first();

            if ($reservaExistente) {
                // Si está cancelada, eliminarla automáticamente para permitir nueva reserva
                if ($reservaExistente->estado === 'cancelado') {
                    $reservaExistente->delete();
                } else {
                    // Si está reservado o atendido, no permitir duplicado
                    return response()->json([
                        'error' => 'Ya existe una reserva activa para este profesional en esta franja horaria'
                    ], 422);
                }
            }

            // Asegurar que estado tenga un valor por defecto
            if (!isset($validated['estado'])) {
                $validated['estado'] = 'reservado';
            }

            // Crear la agenda
            $agenda = new Agenda();
            $agenda->fecha = $validated['fecha'];
            $agenda->id_franja = $validated['id_franja'];
            $agenda->id_profesional = $validated['id_profesional'];
            $agenda->id_cliente = $validated['id_cliente'];
            $agenda->procedimiento = $validated['procedimiento'] ?? null;
            $agenda->estado = $validated['estado'];
            
            // GUARDAR el registro en la base de datos
            $agenda->save();

            // Cargar relaciones
            $agenda->load(['franjaHoraria', 'profesional', 'cliente']);

            return response()->json($agenda, 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Error de validación:', [
                'errors' => $e->errors(),
                'message' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Error de validación', 'details' => $e->errors()], 422);

        } catch (\Exception $e) {
            \Log::error('=== ERROR EN AGENDA STORE ===', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Error al crear la reserva',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $response = Agenda::with(['franjaHoraria', 'profesional', 'cliente'])->findOrFail($id);
            return response()->json($response, 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'fecha' => 'required|date',
            'id_franja' => 'required|integer',
            'id_profesional' => 'required|integer',
            'id_cliente' => 'required|integer',
            'procedimiento' => 'nullable|string',
            'estado' => 'in:reservado,atendido,cancelado',
        ]);

        try {
            $agenda = Agenda::findOrFail($id);

            // Verificar que no exista otra reserva para esa fecha, franja y profesional
            $existe = Agenda::where('fecha', $request->fecha)
                ->where('id_franja', $request->id_franja)
                ->where('id_profesional', $request->id_profesional)
                ->where('id_agenda', '!=', $id)
                ->whereIn('estado', ['reservado', 'atendido'])
                ->exists();

            if ($existe) {
                return response()->json([
                    'error' => 'Ya existe una reserva para este profesional en esta franja horaria'
                ], 422);
            }

            $agenda->update($request->all());
            $agenda->load(['franjaHoraria', 'profesional', 'cliente']);

            return response()->json($agenda, 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $agenda = Agenda::findOrFail($id);
            $agenda->delete();
            return response()->json(['message' => 'Agenda eliminada exitosamente'], 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Cambiar estado de la agenda
     */
    public function cambiarEstado(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'estado' => 'required|in:reservado,atendido,cancelado',
        ]);

        try {
            $agenda = Agenda::findOrFail($id);
            $agenda->estado = $request->estado;
            $agenda->save();
            $agenda->load(['franjaHoraria', 'profesional', 'cliente']);

            return response()->json($agenda, 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }
}
