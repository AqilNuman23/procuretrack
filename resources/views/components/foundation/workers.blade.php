@props(['users'])
<label class="field"><span>Workers <span class="required">*</span></span><select name="users[]" multiple required size="5">@foreach($users as $person)<option value="{{ $person->USNo }}">{{ $person->USName }} · {{ $person->USWorkerID }}</option>@endforeach</select><small>Select one or more workers. Hold Ctrl / Command to select several.</small></label>
