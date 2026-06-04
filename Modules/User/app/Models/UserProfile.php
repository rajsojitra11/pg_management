<?php

namespace Modules\User\Models;

use App\Traits\HasActivityLogging;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\City\Models\City;
use Modules\State\Models\State;
use Modules\User\Database\Factories\UserProfileFactory;

class UserProfile extends Model
{
    use HasActivityLogging, HasFactory, SoftDeletes;

    protected static function newFactory(): UserProfileFactory
    {
        return UserProfileFactory::new();
    }

    protected $table = 'user_profile';

    protected $primarykey = 'id';

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    /**
     * Override logging configuration to log UserProfile changes to User logs
     */
    protected function getLoggingConfig(): array
    {
        return [
            'log_model' => UserActivityLog::class,
            'foreign_key' => 'user_id_acting_on', // Profile changes target this user
            'name_field' => 'user_id',
            'model_name' => 'UserProfile',
            'system_remarks' => [
                'created' => __('user::message.system_profile_created', ['name' => '{user.name}']),
                'updated' => __('user::message.system_profile_updated', ['name' => '{user.name}']),
                'deleted' => __('user::message.system_profile_deleted', ['name' => '{user.name}']),
                'restored' => __('user::message.system_profile_restored', ['name' => '{user.name}']),
            ],
        ];
    }

    /**
     * Override the name field for logging
     * This returns the actual name value to be logged
     */
    protected function getNameField(): string
    {
        // Get the user's name through the relationship
        return $this->user ? $this->user->name : 'Unknown User';
    }

    /**
     * Get the foreign key value for logging (which user this profile belongs to)
     */
    protected function getForeignKeyValue(): mixed
    {
        return $this->user_id;
    }
}
