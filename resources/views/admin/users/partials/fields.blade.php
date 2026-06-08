@php
    use App\Models\User;
    /** @var User|null $user */
    $selectedRoleId = old('role_id', $user?->roles->first()?->id);
@endphp

<div class="row gy-3">
    <div class="col-md-6">
        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name" class="form-control" required maxlength="100"
            value="{{ old('name', $user?->name) }}">
    </div>
    <div class="col-md-6">
        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
        <input type="email" name="email" id="email" class="form-control" required maxlength="255"
            value="{{ old('email', $user?->email) }}" autocomplete="email">
    </div>
    <div class="col-md-6">
        <label for="password" class="form-label">Password @if ($user === null)
                <span class="text-danger">*</span>
            @else
                <span class="text-muted fs-12">(leave blank to keep unchanged)</span>
            @endif
        </label>
        <input type="password" name="password" id="password" class="form-control"
            autocomplete="new-password" {{ $user === null ? 'required' : '' }}>
    </div>
    <div class="col-md-6">
        <label for="password_confirmation" class="form-label">Confirm password @if ($user === null)
                <span class="text-danger">*</span>
            @endif
        </label>
        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control"
            autocomplete="new-password" {{ $user === null ? 'required' : '' }}>
    </div>
    <div class="col-md-6">
        <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
        <input type="text" name="phone" id="phone" class="form-control" maxlength="10" inputmode="numeric"
            value="{{ old('phone', $user?->phone) }}" required>
    </div>
    <div class="col-md-6">
        <label for="whatsapp_number" class="form-label">WhatsApp</label>
        <input type="text" name="whatsapp_number" id="whatsapp_number" class="form-control" maxlength="10"
            inputmode="numeric" value="{{ old('whatsapp_number', $user?->whatsapp_number) }}">
    </div>
    <div class="col-md-6">
        <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
        <select name="role_id" id="role_id" class="form-select" required>
            <option value="">— Select role —</option>
            @foreach ($roles as $role)
                <option value="{{ $role->id }}"
                    {{ (int) $selectedRoleId === (int) $role->id ? 'selected' : '' }}>
                    {{ $role->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6 d-flex align-items-end">
        @php
            $active = old('is_active', $user ? ($user->is_active ? '1' : '0') : '1');
        @endphp
        <input type="hidden" name="is_active" value="0">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                {{ $active === '1' || $active === true || $active === 1 ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Account active</label>
        </div>
    </div>
</div>
