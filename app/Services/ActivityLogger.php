<?php

namespace App\Services;

use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;

class ActivityLogger
{
    private const SENSITIVE = ['password','password_confirmation','token','access_token','refresh_token','secret','api_key','remember_token'];

    public function log(?User $user, string $action, ?string $table = null, mixed $recordId = null, mixed $old = null, mixed $new = null): ?SystemLog
    {
        if (!app()->bound('db') || !Schema::hasTable('system_logs')) return null;
        $request = app()->runningInConsole() ? null : request();
        return SystemLog::create(['user_id'=>$user?->id,'action'=>$action,'table_name'=>$table,'record_id'=>$recordId ? (string)$recordId : null,'method'=>$request?->method(),'url'=>$request?->fullUrl(),'ip_address'=>$request?->ip(),'old_values'=>$this->clean($old),'new_values'=>$this->clean($new)]);
    }

    public function clean(mixed $value): mixed
    {
        if ($value instanceof UploadedFile || is_resource($value)) return '[dihapus: file/binary]';
        if ($value instanceof \BackedEnum) return $value->value;
        if ($value instanceof \DateTimeInterface) return $value->format(DATE_ATOM);
        if (is_object($value)) $value = method_exists($value, 'toArray') ? $value->toArray() : get_object_vars($value);
        if (is_array($value)) { $result=[]; foreach($value as $key=>$item){$keyString=(string)$key;$result[$keyString]=in_array(strtolower($keyString),self::SENSITIVE,true)?'[disamarkan]':$this->clean($item);} return $result; }
        if (is_string($value) && strlen($value)>0 && preg_match('//u',$value)!==1) return '[dihapus: binary]';
        return $value;
    }
}
