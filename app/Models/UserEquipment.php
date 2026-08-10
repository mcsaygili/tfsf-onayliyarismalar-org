<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Üye'nin (fotoğrafçı) sahip olduğu ekipman envanteri satırı — bkz. proje
 * planı "Ekipmanlarım". Photos gibi soft-delete yok.
 */
#[Fillable(['user_id', 'equipment_model_id', 'notes'])]
class UserEquipment extends Model
{
    use HasUuids;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ->withTrashed(): EquipmentModel'in SoftDeletes global scope'u, EYS admin
     * bir modeli soft-delete/pasif ettiğinde kullanıcının zaten kayıtlı
     * ekipmanının adının sessizce null dönmesine yol açar — bu yüzden mevcut
     * sahiplik ilişkisi withTrashed() ile tanımlanıyor. Yeni kayıt seçim
     * dropdown'ları yine EquipmentModel::active() kullanır.
     */
    public function equipmentModel(): BelongsTo
    {
        return $this->belongsTo(EquipmentModel::class)->withTrashed();
    }

    public function photos(): BelongsToMany
    {
        return $this->belongsToMany(Photo::class, 'photo_equipment');
    }
}
