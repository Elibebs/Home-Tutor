<?php

namespace App\Utilities;

use Illuminate\Http\Request;

class FileUtil{

    public static function getFileToSave(Request $request, $file){
         $file->file_extension = $request->file('img')->extension();
         $file->file_mime_type = $request->file('img')->getMimeType();
         $file->file_size = FileUtil::getFileSizeUnit($request->file('img')->getSize());
         $file->display_name = isset($request->all()['display_name']) ? $request->all()['display_name'] : '';

         if(isset(auth()->user)){
             $file->system_user_id = auth()->user()->id;
         }
         return $file;
    }

	public static function getFileSizeUnit($bytes){
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }

        return $bytes;
    }
}
