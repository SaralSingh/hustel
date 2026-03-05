<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Room extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'm3u8_url', 'access_key', 'referer_url'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
            $model->access_key = Str::random(8);
        });
    }

    public function messages()
    {
        return $this->hasMany(RoomMessage::class);
    }
}
