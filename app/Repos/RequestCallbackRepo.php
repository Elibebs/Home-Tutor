<?php

namespace App\Repos;

use App\Models\Rating;
use App\Models\RequestCallback;
use App\Utilities\Constants;
use Illuminate\Support\Facades\Log;

class RequestCallbackRepo extends BaseRepo
{
    public function __construct(RequestCallback $offer)
    {
        $this->model = $offer;
    }

    public function addRequestCallback(Array $data)
    {
        $callback = new RequestCallback;

        $callback->worker_id = $data['worker_id'];
        $callback->phone = $data['phone'];
        $callback->email = $data['email'];
        $callback->message = $data['message'];

        if ($callback->save()) {
            return $callback;
        }
        return null;
    }

    public function getRequestCallbacks($filters, $identifierColumn, $identifier)
    {
        $pageSize = $filters['pageSize'] ?? 20;
        $predicate = RequestCallback::query();
        foreach ($filters as $key => $filter) {
            if (in_array($key, Constants::FILTER_PARAM_IGNORE_LIST)) {
                continue;
            }

            $predicate->where($key, $filter);
        }

        $callbacks = $predicate->where($identifierColumn, $identifier)
            ->with(array('worker'=>function($query){
                $query->select('worker_id', 'name', 'ratings');
            }))
            ->orderBy("created_at", "DESC")
            ->paginate($pageSize);

            foreach ($callbacks as $callback) {
                if(isset($callback['worker']))
                {
                    $callback['worker']['image'] = isset($callback['worker']['image']) ?
                    url("/api/user/image/" . $callback['worker']['image']['name']) : null;
                }
                // get rating
                $callback['worker']['number_reviews'] = Rating::where("worker_id", $callback['worker_id'])->count();
            }

        return $callbacks;
    }

    public function getRequestCallback($Id){
        return RequestCallback::where("id", $Id)->first();
    }

    // public function changeStatus($status, $Id)
    // {
    //     $callback = RequestCallback::where("id", $Id)->first();
    //     if ($callback) {
    //         $callback->status = $status;

    //         if ($callback->update()) {
    //             return $callback;
    //         }
    //     }

    //     return null;
    // }
}
