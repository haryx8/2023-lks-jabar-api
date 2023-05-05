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
            'deadline' => 'date|after:tomorrow',
        ]);
        if (auth()->user()->role == 1) {
            $data = Polls::create([
                'title' => $request->title,
                'description' => $request->description,
                'deadline' => $request->deadline,
                'created_by' => auth()->payload()->get('sub'),
            ]);

            DB::table('choices')->insert(
                array(
                    ['choice' => 'Windows','poll_id' => $data->id, "created_at" => \Carbon\Carbon::now(), "updated_at" => \Carbon\Carbon::now()],
                    ['choice' => 'Android','poll_id' => $data->id, "created_at" => \Carbon\Carbon::now(), "updated_at" => \Carbon\Carbon::now()],
                    ['choice' => 'Linux','poll_id' => $data->id, "created_at" => \Carbon\Carbon::now(), "updated_at" => \Carbon\Carbon::now()],
                    ['choice' => 'BSD','poll_id' => $data->id, "created_at" => \Carbon\Carbon::now(), "updated_at" => \Carbon\Carbon::now()],
                )
            );
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
                $raw = Polls::select('polls.*','users.username')
                ->leftJoin('users', 'polls.created_by', '=', 'users.id')
                ->where('polls.id', request()->segment(3))->get();
                if ($raw->count() > 0) {
                    return response()->json([
                        'data' => Polls::select('polls.*','users.username')
                        ->leftJoin('users', 'polls.created_by', '=', 'users.id')
                        ->where('polls.id', request()->segment(3))->get(),
                    ]);
                }else{
                    return $this->invalid_request();
                }
            }
        }else{
            $polls = Polls::select('polls.*','users.username')
                ->leftJoin('users', 'polls.created_by', '=', 'users.id')->get();
            $choices = Polls::select('polls.id AS poll_id','choices.id','choices.choice')
            ->rightJoin('choices', 'polls.id', '=', 'choices.poll_id')->get();

            $dpools = json_decode($polls, true);
            foreach ($polls as $pid => $praw) {
                $dpools[$pid] = array_merge($dpools[$pid], array("choices" => array()));
                $dchoices = json_decode($choices, true);
                $list = array();
                foreach ($choices as $cid => $craw) {
                    if ($dpools[$pid]['id'] == $dchoices[$cid]['poll_id']) {
                        array_push($list, array($dchoices[$cid]['id'] => $dchoices[$cid]['choice']));
                    }
                }
                $dpools[$pid] = array_merge($dpools[$pid], array("choices" => $list));
            }
                
            return response()->json([
                'data' => $dpools,
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
