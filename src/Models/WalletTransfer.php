<?php

namespace Fintech\Remit\Models;

use Fintech\Core\Traits\AuditableTrait;
use Fintech\Remit\Traits\AuthRelations;
use Fintech\Remit\Traits\BusinessRelations;
use Fintech\Remit\Traits\MetaDataRelations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WalletTransfer extends Model
{
    use AuditableTrait;
    use AuthRelations;
    use BusinessRelations;
    use MetaDataRelations;
    use SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'orders';

    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    protected $appends = ['links'];

    protected $casts = ['order_data' => 'array', 'restored_at' => 'datetime'];

    protected $hidden = ['creator_id', 'editor_id', 'destroyer_id', 'restorer_id'];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    public function currentStatus(): mixed
    {
        return $this->status;
    }
    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /**
     * @return array
     */
    public function getLinksAttribute()
    {
        $primaryKey = $this->getKey();

        $links = [
            'show' => action_link(route('remit.wallet-transfers.show', $primaryKey), __('core::messages.action.show'), 'get'),
            'update' => action_link(route('remit.wallet-transfers.update', $primaryKey), __('core::messages.action.update'), 'put'),
            'destroy' => action_link(route('remit.wallet-transfers.destroy', $primaryKey), __('core::messages.action.destroy'), 'delete'),
            'restore' => action_link(route('remit.wallet-transfers.restore', $primaryKey), __('core::messages.action.restore'), 'post'),
        ];

        if ($this->getAttribute('deleted_at') == null) {
            unset($links['restore']);
        } else {
            unset($links['destroy']);
        }

        return $links;
    }

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
}
