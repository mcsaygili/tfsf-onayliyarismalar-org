<div class="mp-rule-fields">
    <label>{{ __('uye.competitions.capture_device') }}<select name="capture_device_id" class="ia-input"><option value="">{{ __('uye.competitions.choose') }}</option>@foreach($captureDevices as $device)<option value="{{ $device->id }}">{{ $device->name }}</option>@endforeach</select></label>
    <fieldset><legend>{{ __('uye.competitions.processing_methods') }}</legend>@foreach($processingMethods as $method)<label><input type="checkbox" name="processing_method_ids[]" value="{{ $method->id }}"> {{ $method->name }}</label>@endforeach</fieldset>
</div>
