<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class userAccessController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get("search");

        $users = User::where("name", "like", "%".$search."%")->orderBy("id", "desc")->paginate(25);

        return response()->json([
            "total" => $users->total(),
            "users" => $users->map(function($user) {
                return[
                    'id'=> $user->id,
                    'name'=> $user->name,
                    'email'=> $user->email,
                    'surname'=> $user->surname,
                    'full_name'=> $user->name . ' ' . $user->surname,
                    'phone'=> $user->phone,
                    'role_id'=> $user->role_id,
                    'role'=> $user->role,
                    'roles'=> $user->roles,
                    'sucursal_id'=> $user->sucursal_id,
                    'type_document'=> $user->type_document,
                    'n_document'=> $user->n_document,
                    'gender'=> $user->gender,
                    'role_id'=> $user->role_id,
                    'avatar'=> $user->avatar ? env('APP_URL').'storage/'.$user->avatar : null,
                    'created_format_at' => $user->created_at->format('Y-m-d h:i A'),
                ];
            }),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $USER_EXIST = User::where("email", $request->email)->first();
        if($USER_EXIST){
            return response()->json([
                "message" => 403,
                "message_text" => "El usuario ya existe"
            ]);
        }

        if($request->hasfile("imagen")){
            $path = Storage::putFile("users", $request->file("imagen"));
            $request->request->add(["avatar" => $path]);
        }

        if($request->password){
            $request->request->add(['password' => bcrypt($request->password)]);
        }

        $user = Role::FindOrFail($request->role_id);
        $user = User::create($request->all());
        $user->assignRole($request->role);
        return response()->json([
            "message" => 200,
            "user" => [
                'id'=> $user->id,
                'name'=> $user->name,
                'email'=> $user->email,
                'surname'=> $user->surname,
                'full_name'=> $user->name . ' ' . $user->surname,
                'phone'=> $user->phone,
                'role_id'=> $user->role_id,
                'role'=> $user->role,
                'roles'=> $user->roles,
                'sucursal_id'=> $user->sucursal_id,
                'type_document'=> $user->type_document,
                'n_document'=> $user->n_document,
                'gender'=> $user->gender,
                'role_id'=> $user->role_id,
                'avatar'=> $user->avatar ? env('APP_URL').'storage/'.$user->avatar : null,
                'created_format_at' => $user->created_at->format('Y-m-d h:i A'),
            ]
        ]);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        $USER_EXIST = User::where("email", $request->email)
                        ->where("id","<>",$id)->first();
        if($USER_EXIST){
            return response()->json([
                "message" => 403,
                "message_text" => "El usuario ya existe"
            ]);
        }

        $user = User::FindOrFail($id);

        if($request->hasfile("imagen")){
            if($user->avatar){
                Storage::delete($user->avatar);
            }
            $path = Storage::putFile("users", $request->file("imagen"));
            $request->request->add(["avatar" => $path]);
        }

        if($request->password){
            $request->request->add(['password' => bcrypt($request->password)]);
        }

        $user->update($request->all());

        if($request->role_id != $user->role_id){
            //El rol anterior
            $role_old = Role::FindOrFail($user->role_id);
            $user->removeRole($role_old);

            //El nuevo rol
            $user = Role::FindOrFail($request->role_id);
            $user->assignRole($request->role);    
        }
        
        return response()->json([
            "message" => 200,
            "user" => [
                'id'=> $user->id,
                'name'=> $user->name,
                'email'=> $user->email,
                'surname'=> $user->surname,
                'full_name'=> $user->name . ' ' . $user->surname,
                'phone'=> $user->phone,
                'role_id'=> $user->role_id,
                'role'=> $user->role,
                'roles'=> $user->roles,
                'sucursal_id'=> $user->sucursal_id,
                'type_document'=> $user->type_document,
                'n_document'=> $user->n_document,
                'gender'=> $user->gender,
                'role_id'=> $user->role_id,
                'avatar'=> $user->avatar ? env('APP_URL').'storage/'.$user->avatar : null,
                'created_format_at' => $user->created_at->format('Y-m-d h:i A'),
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::FindOrFail($id);
        if($user->avatar){
            Storage::delete($user->avatar);
        }
        $user->delete();
        return response()->json([
            "message" => 200,
        ]);
    }
}
