<?php

namespace App\Http\Requests\Uye;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Sadece `notes` düzenlenebilir — hangi modelin kayıtlı olduğunu değiştirmek
 * isteyen kullanıcı silip yeniden ekler (PhotoUpdateRequest'in dosya
 * alanını hariç tutmasıyla aynı gerekçe). Bu ayrıca, EYS bir modeli
 * pasife alırsa (bkz. UserEquipment::equipmentModel() withTrashed() notu)
 * kullanıcının mevcut kaydını sadece not güncellemek için tekrar aktif bir
 * modelmiş gibi seçmesini gerektirmeyen bir tasarım.
 */
class EquipmentUpdateRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
