@props(['title' => 'Employee portal'])

@php
    $authUser = auth()->user();
    $userInitial = $authUser ? mb_strtoupper(mb_substr($authUser->name, 0, 1, 'UTF-8')) : '?';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr" data-nav-layout="vertical" data-theme-mode="light"
    data-header-styles="light" data-width="fullwidth" data-menu-styles="light" data-toggled="close">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} — {{ $companyDisplayName ?? 'ManufactureERP' }}</title>
    <link rel="stylesheet" href="{{ asset('libs/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('css/icons.css') }}">
    <link rel="stylesheet" href="{{ asset('libs/node-waves/waves.min.css') }}">
    <link rel="stylesheet" href="{{ asset('libs/simplebar/simplebar.min.css') }}">
    <link rel="stylesheet" href="{{ asset('libs/toastify-js/src/toastify.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modules/loader.css') }}">
    @stack('styles')
</head>

<body>
    <x-ui.loader />

    <div class="page">
        <header class="app-header sticky" id="header">
            <div class="main-header-container container-fluid">
                <div class="header-content-left">
                    <div class="header-element">
                        <div class="horizontal-logo d-lg-none">
                            <a href="{{ route('employee.dashboard') }}" class="header-logo">
                                <img src="{{ asset('images/brand-logos/logo.png') }}" alt="{{ $companyDisplayName ?? 'ERP' }}"
                                    class="desktop-logo" style="max-height:36px;width:auto;object-fit:contain;">
                            </a>
                        </div>
                    </div>
                    <div class="header-element">
                        <a aria-label="Toggle navigation" class="sidemenu-toggle header-link" data-bs-toggle="sidebar"
                            href="javascript:void(0);">
                            <svg xmlns="http://www.w3.org/2000/svg" class="header-link-icon menu-btn" width="32"
                                height="32" fill="#000000" viewBox="0 0 256 256">
                                <path
                                    d="M224,128a8,8,0,0,1-8,8H40a8,8,0,0,1,0-16H216A8,8,0,0,1,224,128ZM40,72H216a8,8,0,0,0,0-16H40a8,8,0,0,0,0,16ZM216,184H40a8,8,0,0,0,0,16H216a8,8,0,0,0,0-16Z">
                                </path>
                            </svg>
                        </a>
                    </div>
                </div>

                <ul class="header-content-right">
                    <li class="header-element dropdown">
                        <a href="javascript:void(0);" class="header-link dropdown-toggle d-flex align-items-center"
                            id="headerUserMenu" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                            aria-expanded="false">
                            <span
                                class="avatar avatar-sm avatar-rounded bg-primary-transparent text-primary fw-semibold">{{ $userInitial }}</span>
                            <span class="d-none d-md-inline ms-2 fw-medium lh-1">{{ $authUser->name }}</span>
                        </a>
                        <ul class="main-header-dropdown dropdown-menu dropdown-menu-end pt-0 overflow-hidden header-profile-dropdown"
                            aria-labelledby="headerUserMenu">
                            <li class="px-3 py-2 border-bottom border-block-end-dashed">
                                <span class="fw-semibold d-block">{{ $authUser->name }}</span>
                                <span class="d-block fs-12 text-muted text-truncate">{{ $authUser->email }}</span>
                            </li>
                            <li>
                                <a href="{{ route('employee.profile.show') }}" class="dropdown-item d-flex align-items-center">
                                    <i class="ri-user-line me-2"></i>My profile
                                </a>
                            </li>
                            <li>
                                <form method="post" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item d-flex align-items-center">
                                        <i class="ri-logout-box-r-line me-2"></i>Log out
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </header>

        <aside class="app-sidebar sticky" id="sidebar">
            <div class="main-sidebar-header">
                <a href="{{ route('employee.dashboard') }}" class="header-logo">
                    <img src="{{ asset('images/brand-logos/logo.png') }}" alt="{{ $companyDisplayName ?? 'ERP' }}"
                        class="desktop-logo" style="max-height:36px;max-width:100%;width:auto;object-fit:contain;">
                </a>
            </div>
            <div class="main-sidebar" id="sidebar-scroll">
                <nav class="main-menu-container nav nav-pills flex-column sub-open">
                    <ul class="main-menu">
                        <li class="slide">
                            <a href="{{ route('employee.dashboard') }}"
                                class="side-menu__item {{ request()->routeIs('employee.dashboard') ? 'active' : '' }}">
                                <i class="ri-dashboard-line side-menu__icon"></i>
                                <span class="side-menu__label">Dashboard</span>
                            </a>
                        </li>
                        @can('hr.attendance.view')
                            <li class="slide">
                                <a href="{{ route('employee.attendance.index') }}"
                                    class="side-menu__item {{ request()->routeIs('employee.attendance.*') ? 'active' : '' }}">
                                    <i class="ri-calendar-check-line side-menu__icon"></i>
                                    <span class="side-menu__label">My attendance</span>
                                </a>
                            </li>
                        @endcan
                            <li class="slide">
                                <a href="{{ route('employee.leave.index') }}"
                                    class="side-menu__item {{ request()->routeIs('employee.leave.*') ? 'active' : '' }}">
                                    <i class="ri-calendar-event-line side-menu__icon"></i>
                                    <span class="side-menu__label">Leave</span>
                                </a>
                            </li>
                        @endcan
                        @can('hr.payslip.view')
                            <li class="slide">
                                <a href="{{ route('employee.payslips.index') }}"
                                    class="side-menu__item {{ request()->routeIs('employee.payslips.*') ? 'active' : '' }}">
                                    <i class="ri-money-cny-circle-line side-menu__icon"></i>
                                    <span class="side-menu__label">Payslips</span>
                                </a>
                            </li>
                        @endcan
                        <li class="slide">
                            <a href="{{ route('employee.profile.show') }}"
                                class="side-menu__item {{ request()->routeIs('employee.profile.*') ? 'active' : '' }}">
                                <i class="ri-user-3-line side-menu__icon"></i>
                                <span class="side-menu__label">Profile</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <div class="main-content app-content">
            <div class="container-fluid">
                {{ $slot }}
            </div>
        </div>
    </div>

    <div id="responsive-overlay"></div>

    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('js/erp-layout-preflight.js') }}"></script>
    <script src="{{ asset('libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/defaultmenu.min.js') }}"></script>
    <script src="{{ asset('libs/node-waves/waves.min.js') }}"></script>
    <script src="{{ asset('libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('libs/toastify-js/src/toastify.js') }}"></script>
    <script src="{{ asset('vendor/jquery-validate/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('vendor/jquery-validate/additional-methods.min.js') }}"></script>
    <script src="{{ asset('js/main.js') }}"></script>
    <script src="{{ asset('js/sticky.js') }}"></script>
    <script src="{{ asset('js/simplebar.js') }}"></script>
    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>

</html>
