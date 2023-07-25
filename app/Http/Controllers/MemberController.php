<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Role;
use App\Models\MemberRole;
use Illuminate\Http\Request;
use Validator;

class MemberController extends Controller
{   
    private $return = [
            "status"  => "error",
            "message" => "",
            "data"    => []
        ];

    public function index(Request $request)
    {
        $return = $this->return;
        
        if($request->role) {
            $roles    = array_map('trim', explode(',', $request->role));
            $roleIds = Role::select('id')->whereIn('slug',$roles)->pluck('id');
            $members = Member::with('roles')->whereHas(
                'roles', function($q) use ($roleIds) {
                    $q->whereIn('role_id', $roleIds);
                }
            )->get();
            
        } else {
            $members = Member::with('roles')->get();
        }

        if($members) {
            $return["status"]   = 'success';
            $return["data"]     = $members;

            return response()->json($return, 200);
        } else {
            
            $return['message'] = 'Error';
            return response()->json($return, 400);
        }        
    }

    public function store(Request $request)
    {
        $return = $this->return;

        $validator = Validator::make($request->all(),[
            'name' => 'required|string',
            'email' => 'required|email|unique:members,email',
            'role' => 'required|array'
        ]);

        if($validator->fails()){
            $return['message'] = $validator->errors();
            return response()->json($return, 400);                
        }        

        $roles      = $request->role;
        $memberData = $request->only(['name', 'email']);
        $member     = Member::create($memberData);        

        if($member) {

            $roleIds = Role::select('id')->whereIn('slug',$roles)->pluck('id');
            $member->roles()->attach($roleIds);

            $return["status"]   = 'success';
            $return["data"]     = $member;

            return response()->json($return, 201);
        } else {
            
            $return['message'] = 'Unable to add a new record.';
            return response()->json($return, 400);
        }        
    }

    public function show(Member $member)
    {
        
        $return = $this->return;

        if($member) {
            
            $memberRoles = $member->roles;

            $return["status"]   = 'success';
            $return["data"]     = $member->toArray();

            return response()->json($return, 200);
        } else {
            
            $return['message'] = 'Record not found.';
            return response()->json($return, 400);
        }
    }

    public function update(Request $request, Member $member)
    {        

        $return = $this->return;

        $validator = Validator::make($request->all(),[
            'name' => 'required|string',
            'email' => 'required|email|unique:members,email,' . $member->id,
            'role' => 'array'
        ]);

        if($validator->fails()){
            $return['message'] = $validator->errors();
            return response()->json($return, 400);                
        }        

        $roles      = $request->role;
        $memberData = $request->only(['name', 'email']);

        $member->update($memberData);                     

        if($member) {

            $roleIds   = Role::select('id')->whereIn('slug',$roles)->pluck('id');
            $member->roles()->sync($roleIds);

            $return["status"]   = 'success';
            $return["data"]     = $member;

            return response()->json($return, 201);
        } else {
            
            $return['message'] = 'Unable to update a record.';
            return response()->json($return, 400);
        }          
    }

    public function destroy(Member $member)
    {
        $return = $this->return;
        
        if($member) {
            $member->delete();

            $return["status"]   = 'success';

            return response()->json($return, 200);
        } else {
            
            $return['message'] = 'Record not found.';
            return response()->json($return, 400);
        }        
    }
}