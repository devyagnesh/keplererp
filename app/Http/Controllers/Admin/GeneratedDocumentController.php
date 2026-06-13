<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneratedDocument;
use App\Support\ErpDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * PDF generation activity log (SRS §21.13).
 */
class GeneratedDocumentController extends Controller
{
    public function index(): View
    {
        $user = request()->user();
        if ($user === null || ! $user->can('finance.reports.view')) {
            abort(403);
        }

        return view('admin.reports.pdf-log-index');
    }

    public function data(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null || ! $user->can('finance.reports.view')) {
            abort(403);
        }

        $query = GeneratedDocument::query()
            ->select(['id', 'document_type', 'module', 'download_name', 'generated_by', 'created_at', 'is_active'])
            ->with('generator:id,name');

        $payload = ErpDataTable::run(
            $query,
            $request,
            function ($q, string $term): void {
                $q->where('download_name', 'like', '%'.$term.'%')
                    ->orWhere('document_type', 'like', '%'.$term.'%');
            },
            ['id', 'document_type', 'created_at'],
        );

        $data = $payload['rows']->map(function (GeneratedDocument $row) {
            return [
                'type' => $row->document_type instanceof \BackedEnum ? $row->document_type->value : (string) $row->document_type,
                'module' => $row->module,
                'name' => $row->download_name,
                'generated_by' => $row->generator?->name ?? '—',
                'created_at' => $row->created_at?->format('Y-m-d H:i'),
                'active' => $row->is_active ? 'Yes' : 'No',
                'action' => $row->is_active
                    ? '<a href="'.e(route('documents.download', $row)).'" class="btn btn-sm btn-outline-secondary btn-wave" target="_blank" rel="noopener">Download</a>'
                    : '—',
            ];
        })->values()->all();

        return response()->json([
            'draw' => $payload['draw'],
            'recordsTotal' => $payload['recordsTotal'],
            'recordsFiltered' => $payload['recordsFiltered'],
            'data' => $data,
        ]);
    }
}
