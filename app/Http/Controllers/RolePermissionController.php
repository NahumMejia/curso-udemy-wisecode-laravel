<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Log;

class RolePermissionController extends Controller
{
    public function index(Request $request){
        $search = $request->get("search");

        $roles = Role::with(["permissions"])
            ->where("name", "like", "%" . $search . "%")
            ->orderBy("id", "desc")
            ->paginate(25);
        
        return response()->json([
            "total" => $roles->total(),
            "roles" => $roles->map(function($rol) {
                $rol->permission_pluck = $rol->permissions->pluck("name");
                $rol->created_at = $rol->created_at->format("Y-m-d h:i A");
                return $rol;
            }),
        ]);
    }

    public function store(Request $request){
        try {
            // Validar datos de entrada
            $request->validate([
                'name' => 'required|string|max:255',
                'permisions' => 'required|array|min:1',
                'permisions.*' => 'required|string'
            ]);

            // Verificar si el rol ya existe (solo para creación)
            if (!$request->has('id') || !$request->id) {
                $IS_ROLE = Role::where("name", $request->name)
                              ->where('guard_name', 'api')
                              ->first();

                if ($IS_ROLE) {
                    return response()->json([
                        "message" => 403,
                        "message_text" => "EL ROL YA EXISTE"
                    ], 403);
                }

                // Crear nuevo rol
                $role = Role::create([
                    'name' => $request->name,
                    'guard_name' => 'api'
                ]);
            } else {
                // Actualizar rol existente
                $role = Role::findOrFail($request->id);
                
                // Verificar que no existe otro rol con el mismo nombre
                $IS_ROLE = Role::where("name", $request->name)
                              ->where("id", "<>", $request->id)
                              ->where('guard_name', 'api')
                              ->first();

                if ($IS_ROLE) {
                    return response()->json([
                        "message" => 403,
                        "message_text" => "EL ROL YA EXISTE"
                    ], 403);
                }

                $role->name = $request->name;
                $role->save();
                
                // Limpiar permisos existentes
                $role->syncPermissions([]);
            }

            // Asignar permisos al rol
            $validPermissions = [];
            foreach ($request->permisions as $permision) {
                $permission = Permission::where('name', $permision)
                                       ->where('guard_name', 'api')
                                       ->first();

                if ($permission) {
                    $validPermissions[] = $permission;
                } else {
                    Log::warning("Permiso no encontrado: $permision");
                }
            }

            if (empty($validPermissions)) {
                return response()->json([
                    "message" => 404,
                    "message_text" => "No se encontraron permisos válidos."
                ], 404);
            }

            // Asignar todos los permisos válidos
            $role->syncPermissions($validPermissions);

            return response()->json([
                "message" => 200,
                "message_text" => "Rol guardado correctamente",
                "role" => [
                    "id" => $role->id,
                    "name" => $role->name,
                    "permissions" => $role->permissions,
                    "permission_pluck" => $role->permissions->pluck("name"),
                    "created_at" => $role->created_at->format("Y-m-d h:i A"),
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                "message" => 422,
                "message_text" => "Datos de validación incorrectos",
                "errors" => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Error al crear/actualizar rol: ' . $e->getMessage());
            
            return response()->json([
                "message" => 500,
                "message_text" => "Error interno del servidor: " . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $role = Role::findOrFail($id);
            $role->delete();

            return response()->json([
                "message" => 200,
                "message_text" => "Rol eliminado correctamente"
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                "message" => 500,
                "message_text" => "Error al eliminar el rol"
            ], 500);
        }
    }
}