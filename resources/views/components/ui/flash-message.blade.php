@if(session('status'))<x-ui.alert type="success" class="mb-5">{{ session('status') }}</x-ui.alert>@endif
@if(session('error'))<x-ui.alert type="error" class="mb-5">{{ session('error') }}</x-ui.alert>@endif
