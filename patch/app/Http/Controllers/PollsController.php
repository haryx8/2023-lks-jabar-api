<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Polls;

class PollsController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => []]);
    }

    public function poll(Request $request)
    {
        $request->validate([
            'title' => 'required|string|min:12|max:255|unique:polls',
            'description' => 'required|string|min:12|max:255',
            'deadline' => 'required|date|after:tomorrow',
            'choices' => 'required|json',
        ]);
        if (auth()->user()->role == 1) {
            $data = Polls::create([
                'title' => $request->title,
                'description' => $request->description,
                'deadline' => $request->deadline,
                'created_by' => auth()->payload()->get('sub'),
            ]);
            
            $choices = json_decode($request->choices, true);
            $list = array();
            foreach ($choices as $key => $val) {
                $list = array_merge($list, array(['choice' => $val,'poll_id' => $data->id, "created_at" => \Carbon\Carbon::now(), "updated_at" => \Carbon\Carbon::now()]));
            }
            DB::table('choices')->insert($list);
            return $this->success_request();
        }else{
            return $this->unauthorized_request();
        }
    }

    public function poll_delete(Request $request)
    {
        if (auth()->user()->role == 1) {
            if (request()->segment(3)) {
                $var['pid'] = $request->segment(3);
                $validator = Validator::make($var, [
                    'pid' => 'required|integer|min:1',
                ]);
                if ($validator->fails()) {
                    return $this->invalid_request();
                }else{
                    $x = Polls::find($var['pid']);

                    if (is_array(json_decode($x,true))) {
                        Polls::find($var['pid'])->delete();
                        return $this->success_request();
                    }else{
                        return $this->invalid_request();
                    }
                }
            }else{
                return $this->invalid_request();
            }
        }else{
            return $this->unauthorized_request();
        }
    }

    public function poll_get(Request $request)
    {
        if (request()->segment(3)) {
            $var['pid'] = $request->segment(3);
            $validator = Validator::make($var, [
                'pid' => 'required|integer|min:1',
            ]);
            if ($validator->fails()) {
                return $this->invalid_request();
            }else{
                // $data = Polls::select('polls.*','users.username',DB::raw("(SELECT JSON_OBJECTAGG(c.id,c.choice) FROM choices c WHERE polls.id = c.poll_id) AS choices"))
                $data = Polls::select('polls.*','users.username',
                DB::raw("(SELECT 0) AS result"),
                DB::raw("JSON_ARRAY((SELECT JSON_GROUP_OBJECT(c.id,c.choice) FROM choices c WHERE polls.id = c.poll_id)) AS choices"))
                ->leftJoin('users', 'polls.created_by', '=', 'users.id')
                ->where('polls.id', request()->segment(3))->get();
                if ($data->count() > 0) {
                    return response()->json([
                        'data' => $data,
                    ]);
                }else{
                    return $this->invalid_request();
                }
            }
        }else{
            $polls = Polls::select(
                'polls.*','users.username',
                DB::raw("(SELECT 0) AS result"),
                DB::raw("JSON_ARRAY((SELECT JSON_GROUP_OBJECT(c.id,c.choice) FROM choices c WHERE polls.id = c.poll_id)) AS choices"))
                ->leftJoin('users', 'polls.created_by', '=', 'users.id')->get();
            return response()->json([
                'data' => $polls,
            ]);
        }
    }

    public function poll_vote(Request $request)
    {
        if (auth()->user()->role == 1) {
            return $this->unauthorized_request();
        }

        return $this->success_request();
    }

    public function invalid_request() {
        return response()->json([
            'message' => 'The given data was invalid',
        ], 422);
    }

    public function success_request() {
        return response()->json([
            'message' => 'success',
        ]);
    }

    public function unauthorized_request() {
        return response()->json([
            'message' => 'Unauthorized',
        ], 401);    
    }
}
