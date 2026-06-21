<?php

namespace Modules\Email\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Email\Http\Requests\DeleteEmailConfigRequest;
use Modules\Email\Http\Requests\StoreEmailConfigRequest;
use Modules\Email\Http\Requests\StoreEmailTemplateRequest;
use Modules\Email\Http\Requests\UpdateEmailConfigRequest;
use Modules\Email\Models\EmailConfig;
use Modules\Email\Models\EmailTemplate;
use Modules\PgManagement\Models\PgManagement;
use Yajra\DataTables\DataTables;

class EmailController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:email-list|email-create', ['only' => ['index', 'storeConfig']]);
        $this->middleware('permission:email-create', ['only' => ['create', 'storeConfig']]);
        $this->middleware('permission:email-edit', ['only' => ['editConfig', 'updateConfig']]);
        $this->middleware('permission:email-delete', ['only' => ['destroyConfig']]);
    }

    public function index()
    {
        $user = auth()->user();
        $pgQuery = PgManagement::select('id', 'pg_name')->where('status', 'active');
        if ($user->hasRole('Pg_Admin')) {
            $pgQuery->where('owner_id', $user->id);
        }
        $pgList = $pgQuery->get();

        $defaultTemplate = EmailTemplate::where('name', 'rent_reminder')->first();

        return view('email::index', compact('pgList', 'defaultTemplate'));
    }

    // ── Config DataTable ──────────────────────────────────────────────

    public function configData()
    {
        $user = auth()->user();
        $query = EmailConfig::with('pg', 'createdBy')->select('id', 'public_id', 'pg_id', 'sender_email', 'sender_name', 'subject_prefix', 'status', 'created_by');

        if ($user->hasRole('Pg_Admin')) {
            $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('pg_name', fn ($row) => $row->pg?->pg_name ?? '—')
            ->addColumn('created_user', fn ($row) => $row->createdBy?->name ?? '—')
            ->addColumn('action', function ($row) {
                $flag = true;
                $show = '';
                $edit = $flag ? 'email-edit' : '';
                $delete = $flag ? 'email-delete' : '';
                $showURL = '';
                $editURL = '';

                return view('layouts-tw.action', compact('row', 'show', 'edit', 'delete', 'showURL', 'editURL'));
            })
            ->escapeColumns([])
            ->make(true);
    }

    // ── Config CRUD ───────────────────────────────────────────────────

    public function storeConfig(StoreEmailConfigRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['created_by'] = auth()->id();

            EmailConfig::create($data);

            DB::commit();

            return response()->json(['status_code' => 200, 'message' => __('email::message.config_created')]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function editConfig($id)
    {
        try {
            $config = EmailConfig::with('pg')->byAnyKey($id)->first();
            if ($config) {
                return response()->json(['status_code' => 200, 'message' => 'Edit config', 'result' => $config]);
            }

            return response()->json(['status_code' => 404, 'message' => 'Config not found.']);
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function updateConfig(UpdateEmailConfigRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $config = EmailConfig::byAnyKey($id)->firstOrFail();
            $data = $request->validated();
            $data['updated_by'] = auth()->id();

            $config->update($data);

            DB::commit();

            return response()->json(['status_code' => 200, 'message' => __('email::message.config_updated')]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function destroyConfig(DeleteEmailConfigRequest $request, $id)
    {
        try {
            $config = EmailConfig::byAnyKey($id)->firstOrFail();
            $config->update(['deleted_by' => auth()->id()]);
            $config->delete();

            return response()->json(['status_code' => 200, 'message' => __('email::message.config_deleted')]);
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    // ── Template ──────────────────────────────────────────────────────

    public function getTemplate()
    {
        $template = EmailTemplate::where('name', 'rent_reminder')->first();

        return response()->json(['status_code' => 200, 'result' => $template]);
    }

    public function previewTemplate(Request $request)
    {
        $subject = $request->input('subject', '');
        $body = $request->input('body', '');

        $sampleData = [
            '{tenant_name}' => 'John Doe',
            '{tenant_email}' => 'john.doe@example.com',
            '{pg_name}' => 'Sai PG Accommodation',
            '{room_no}' => 'A-101',
            '{checkin_date}' => '15-01-2026',
            '{monthly_rent}' => '8,500.00',
            '{due_date}' => now()->addDays(2)->format('d-m-Y'),
            '{current_month}' => now()->format('F Y'),
            '{sender_name}' => 'Property Manager',
        ];

        $renderedSubject = str_replace(array_keys($sampleData), array_values($sampleData), $subject);
        $renderedBody = str_replace(array_keys($sampleData), array_values($sampleData), $body);

        return response()->json([
            'status_code' => 200,
            'subject' => $renderedSubject,
            'body' => $renderedBody,
        ]);
    }

    public function saveTemplate(StoreEmailTemplateRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            $template = EmailTemplate::where('name', 'rent_reminder')->first();
            if ($template) {
                $data['updated_by'] = auth()->id();
                $template->update($data);
            } else {
                $data['name'] = 'rent_reminder';
                $data['is_default'] = true;
                $data['created_by'] = auth()->id();
                EmailTemplate::create($data);
            }

            DB::commit();

            return response()->json(['status_code' => 200, 'message' => __('email::message.template_saved')]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }
}
