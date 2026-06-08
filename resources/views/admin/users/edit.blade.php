<x-layouts.app title="Edit user">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Edit user</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $user->name }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">{{ $user->name }}</div>
                </div>
                <div class="card-body">
                    <form id="userForm" method="POST" action="{{ route('admin.users.update', $user) }}" novalidate>
                        @csrf
                        @method('PUT')
                        @include('admin.users.partials.fields', ['user' => $user, 'roles' => $roles])
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary" id="userSubmit">Save changes</button>
                            <a href="{{ route('admin.users.index') }}" class="btn btn-light ms-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            window.userFormSubmitUrl = @json(route('admin.users.update', $user));
            window.userFormMethod = 'PUT';
            window.usersIndexUrl = @json(route('admin.users.index'));
        </script>
        <script src="{{ asset('js/modules/users/user-form.js') }}"></script>
    @endpush
</x-layouts.app>
