<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Hyn\Tenancy\Traits\UsesSystemConnection;
use App\Utilities\Generators;
use App\Utilities\FileUtil;
use Illuminate\Support\Facades\Log;

use Image as InterventionImage;

class SystemImage extends Model
{
    use UsesSystemConnection;
    protected $primaryKey = "id";
    protected $table = "images";
/**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'img','imageable_id','imageable_type',
    ];

    public function imageable()
    {
        return $this->morphTo();
    }

    public function systemUser(){
        return $this->belongsTo('App\Models\SystemUser','system_user_id');
    }

    static public function saveImage($image, $model){
        $base64 = base64_encode($image);

        $picture = new SystemImage;
        $picture->name = Generators::generateUniq();
        $picture->img = $base64;
        $model->image()->save($picture);
    }

    static public function updateImage($image, $model){
        $base64 = base64_encode($image);

        $picture = new SystemImage;
        $picture->name = Generators::generateUniq();
        $picture->img = $base64;
        $model->image()->delete();
        $model->image()->save($picture);
    }

    static public function saveFile($file, $image_file, $model){
        $base64 = base64_encode($image_file);

        $file->name = Generators::generateUniq();
        $file->img = $base64;

        $model->files()->save($file);
    }

    static public function saveBase64Image($base64, $model){
        $image = new SystemImage;
        $image->name = Generators::generateUniq();
        $image->img = $base64;
        $model->image()->save($image);
    }

    static public function updateBase64Image($base64, $model, $model_id){
        $image = new SystemImage;
        $image->name = Generators::generateUniq();
        $image->img = $base64;

        $hasImage = $model->image()
        ->where('imageable_id', $model_id)
        ->where('imageable_type', get_class($model))
        ->count() > 0;

        Log::notice("hasImage={$hasImage}");
        if($hasImage){
            Log::notice("image()->update()");
            $model->image()->update(['img'=>$image->img]);
        }else{
            Log::notice("image()->save()");
            $model->image()->save($image);
        }
        Log::notice("Model saved... {$model}");
        Log::notice("Image saved... {$image}");
    }

    static public function saveBase64StringFile($file, Array $data, $model){

        $file->name = Generators::generateUniq();
        $file->img = $data['img'];
        $file->display_name = $data['display_name'];
        $file->file_extension = $data['extension'];
        $file->file_size = FileUtil::getFileSizeUnit($data['size']);

        $model->files()->save($file);
    }

    static public function updateBase64StringFile($file, Array $data, $model){
        $file->img = $data['img'];
        $file->display_name = $data['display_name'];
        $file->file_extension = $data['extension'];
        $file->file_size = FileUtil::getFileSizeUnit($data['size']);

        $file->update();
    }

    static public function resize($width, $height, $image){
        $img = InterventionImage::make($image);
        $img->resize($width, $height);

        return $img->encode();
    }

    static public function crop($width, $height, $image){
        $img = InterventionImage::make($image);
        $img->crop($width, $height);

        return $img->encode();
    }

    static public function sharpen($amount, $image){
        $img = InterventionImage::make($image);
        $img->sharpen($amount);

        return $img->encode();
    }
}
