<?php

namespace App\Repos;

use App\Models\RatingUser;
use App\Models\UserComplaint;
use App\Models\ComplaintChat;
use App\Utilities\Constants;
use Illuminate\Support\Facades\Log;
class UserComplaintRepo extends BaseRepo
{
    public function __construct(UserComplaint $complaint)
    {
        $this->model = $complaint;
    }

    public function addComplaint(Array $data)
    {
        $complaint = new UserComplaint;

        $complaint->user_id = $data['user_id'];
        $complaint->complaint = $data['complaint'];
        $complaint->item_number = $data['item_number'];
        $complaint->complaint_type = $data['complaint_type'];
        $complaint->status = Constants::USER_COMPLAINT_STATUS_PENDING;

        if ($complaint->save()) {
            return $complaint;
        }
        return null;
    }

    public function sendMessage($id, Array $data){
        $message = new ComplaintChat;

        $message->user_id = $data['user_id'];
        $message->user_complaint_id = $id;
        $message->message =  $data['message'];

        if ($message->save()){
            return $message;
        }

        return null;
    }

    public function getChat($filters, $id){
        $pageSize = $filters['pageSize'] ?? 20;
        $predicate = ComplaintChat::query();
        foreach ($filters as $key => $filter) {
            if (in_array($key, Constants::FILTER_PARAM_IGNORE_LIST)) {
                continue;
            }

            $predicate->where($key, $filter);
        }

        $chats = $predicate->where('user_complaint_id', $id)
            ->orderBy("created_at", "ASC")
            ->paginate($pageSize);

        return $chats;
    }

    public function getComplaints($filters, $identifierColumn, $identifier)
    {
        $pageSize = $filters['pageSize'] ?? 20;
        $predicate = UserComplaint::query();
        foreach ($filters as $key => $filter) {
            if (in_array($key, Constants::FILTER_PARAM_IGNORE_LIST)) {
                continue;
            }

            $predicate->where($key, $filter);
        }

        $complaints = $predicate->where($identifierColumn, $identifier)
            ->with(array('user'=>function($query){
                $query->select('user_id', 'name');
            }))
            ->with('chats')
            ->orderBy("updated_at", "ASC")
            ->paginate($pageSize);

            foreach ($complaints as $complaint) {
                if(isset($complaint['user']))
                {
                    $complaint['user']['image_url'] = isset($complaint['user']['image']) ?
                        (config("app.url") . "/image/" .$complaint['user']['image']['name']) : null;

                    unset($complaint['user']['image']);
                }
                // get rating
                $complaint['user']['number_reviews'] = RatingUser::where("user_id", $complaint['user_id'])->count();
            }

        return $complaints;
    }

    public function getComplaint($Id){
        return UserComplaint::where("id", $Id)->first();
    }

    public function changeStatus($status, $Id)
    {
        $complaint = UserComplaint::where("id", $Id)->first();
        if ($complaint) {
            $complaint->status = $status;

            if ($complaint->update()) {
                return $complaint;
            }
        }

        return null;
    }
}
