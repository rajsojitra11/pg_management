<?php

namespace Modules\EnvVariable\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\EnvVariable\Http\Requests\DeleteEnvVariableRequest;
use Modules\EnvVariable\Http\Requests\StoreEnvVariableRequest;
use Modules\EnvVariable\Http\Requests\UpdateEnvVariableRequest;
use Modules\EnvVariable\Models\EnvVariable;
use Modules\EnvVariable\Services\EnvFileService;

class EnvVariableController extends Controller
{
    protected EnvFileService $envFileService;

    public function __construct(EnvFileService $envFileService)
    {
        $this->middleware('permission:env-variable-list|env-variable-create|env-variable-edit|env-variable-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:env-variable-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:env-variable-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:env-variable-delete', ['only' => ['destroy']]);

        $this->envFileService = $envFileService;
    }

    public function index(): View
    {
        $envVariables = EnvVariable::query()
            ->with(['createdBy', 'updatedBy'])
            ->orderBy('key')
            ->get();

        return view('envvariable::index', compact('envVariables'));
    }

    public function create(): View
    {
        return view('envvariable::create');
    }

    public function store(StoreEnvVariableRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            // The HasActivityLogging trait automatically handles user_remark from the request
            $envVariable = EnvVariable::create($data);

            // Update .env file if requested
            if ($request->has('sync_env_file') && $request->sync_env_file) {
                $value = $envVariable->is_encrypted ? $envVariable->decrypted_value : $envVariable->value;
                $this->envFileService->updateEnvFile($envVariable->key, $value);
            }

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'message' => __('envvariable::message.created_successfully'),
                'data' => route('env-variable.index'),
            ]);

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Environment variable creation failed', [
                'error' => $e->getMessage(),
                'data' => $request->except(['value']), // Don't log sensitive values
            ]);

            return response()->json([
                'status_code' => 500,
                'message' => __('lang.common.error_occurred'),
            ]);
        }
    }

    public function show(EnvVariable $envVariable): View
    {
        $envVariable->load(['createdBy', 'updatedBy', 'logs.user']);

        return view('envvariable::show', compact('envVariable'));
    }

    public function edit(EnvVariable $envVariable): View
    {
        return view('envvariable::edit', compact('envVariable'));
    }

    public function update(UpdateEnvVariableRequest $request, EnvVariable $envVariable): JsonResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['updated_by'] = Auth::id();

            // The HasActivityLogging trait automatically handles user_remark from the request
            $envVariable->update($data);

            // Update .env file if requested
            if ($request->has('sync_env_file') && $request->sync_env_file) {
                $value = $envVariable->is_encrypted ? $envVariable->decrypted_value : $envVariable->value;
                $this->envFileService->updateEnvFile($envVariable->key, $value);
            }

            // Handle automatic cache clear and composer dump-autoload for variables that require restart
            if ($envVariable->requires_restart) {
                $envVariable->handlePostUpdate();
            }

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'message' => __('envvariable::message.updated_successfully'),
                'data' => route('env-variable.index'),
            ]);

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Environment variable update failed', [
                'error' => $e->getMessage(),
                'id' => $envVariable->id,
                'data' => $request->except(['value', 'user_remark']),
            ]);

            return response()->json([
                'status_code' => 500,
                'message' => __('lang.common.error_occurred'),
            ]);
        }
    }

    public function destroy(DeleteEnvVariableRequest $request, EnvVariable $envVariable): JsonResponse
    {
        DB::beginTransaction();
        try {
            $envVariable->updated_by = Auth::id();
            $envVariable->deleted_by = Auth::id();
            $envVariable->save();

            // The HasActivityLogging trait automatically handles user_remark from the request
            $envVariable->delete();

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'message' => __('envvariable::message.deleted_successfully'),
            ]);

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Environment variable deletion failed', [
                'error' => $e->getMessage(),
                'id' => $envVariable->id,
            ]);

            return response()->json([
                'status_code' => 500,
                'message' => __('lang.common.error_occurred'),
            ]);
        }
    }

    public function clearAllCaches(): JsonResponse
    {
        try {
            $envVariable = new EnvVariable;
            $result = $envVariable->clearAllCaches();

            if ($result) {
                return response()->json([
                    'status_code' => 200,
                    'message' => __('envvariable::message.all_caches_cleared'),
                ]);
            } else {
                return response()->json([
                    'status_code' => 500,
                    'message' => __('envvariable::message.cache_clear_failed'),
                ]);
            }

        } catch (Exception $e) {
            Log::error('All caches clear failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status_code' => 500,
                'message' => __('lang.common.error_occurred'),
            ]);
        }
    }

    public function clearConfigCache(): JsonResponse
    {
        try {
            $envVariable = new EnvVariable;
            $result = $envVariable->clearConfigCache();

            if ($result) {
                return response()->json([
                    'status_code' => 200,
                    'message' => __('envvariable::message.config_cache_cleared'),
                ]);
            } else {
                return response()->json([
                    'status_code' => 500,
                    'message' => __('envvariable::message.cache_clear_failed'),
                ]);
            }

        } catch (Exception $e) {
            Log::error('Config cache clear failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status_code' => 500,
                'message' => __('lang.common.error_occurred'),
            ]);
        }
    }

    public function clearApplicationCache(): JsonResponse
    {
        try {
            $envVariable = new EnvVariable;
            $result = $envVariable->clearApplicationCache();

            if ($result) {
                return response()->json([
                    'status_code' => 200,
                    'message' => __('envvariable::message.app_cache_cleared'),
                ]);
            } else {
                return response()->json([
                    'status_code' => 500,
                    'message' => __('envvariable::message.cache_clear_failed'),
                ]);
            }

        } catch (Exception $e) {
            Log::error('Application cache clear failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status_code' => 500,
                'message' => __('lang.common.error_occurred'),
            ]);
        }
    }

    public function clearRoutesAndViews(): JsonResponse
    {
        try {
            $envVariable = new EnvVariable;
            $result = $envVariable->clearRoutesAndViews();

            if ($result) {
                return response()->json([
                    'status_code' => 200,
                    'message' => __('envvariable::message.routes_views_cleared'),
                ]);
            } else {
                return response()->json([
                    'status_code' => 500,
                    'message' => __('envvariable::message.cache_clear_failed'),
                ]);
            }

        } catch (Exception $e) {
            Log::error('Routes and views clear failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status_code' => 500,
                'message' => __('lang.common.error_occurred'),
            ]);
        }
    }

    public function composerDumpAutoload(): JsonResponse
    {
        try {
            $envVariable = new EnvVariable;
            $result = $envVariable->runComposerDumpAutoload();

            if ($result) {
                return response()->json([
                    'status_code' => 200,
                    'message' => __('envvariable::message.autoload_regenerated'),
                ]);
            } else {
                return response()->json([
                    'status_code' => 500,
                    'message' => __('envvariable::message.autoload_failed'),
                ]);
            }

        } catch (Exception $e) {
            Log::error('Composer dump-autoload failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status_code' => 500,
                'message' => __('lang.common.error_occurred'),
            ]);
        }
    }

    public function syncToEnv(): JsonResponse
    {
        try {
            $result = EnvVariable::syncToEnvFile();

            if ($result) {
                $activeVariablesCount = EnvVariable::where('is_active', true)->count();

                return response()->json([
                    'status_code' => 200,
                    'message' => __('envvariable::message.synced_successfully', ['count' => $activeVariablesCount]),
                ]);
            } else {
                return response()->json([
                    'status_code' => 500,
                    'message' => __('envvariable::message.sync_failed'),
                ]);
            }

        } catch (Exception $e) {
            Log::error('Environment sync failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status_code' => 500,
                'message' => __('envvariable::message.sync_failed'),
            ]);
        }
    }
}
