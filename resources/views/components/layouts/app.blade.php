@props(['title' => 'ManufactureERP'])

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
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="{{ asset('libs/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('css/icons.css') }}">
    <link rel="stylesheet" href="{{ asset('libs/node-waves/waves.min.css') }}">
    <link rel="stylesheet" href="{{ asset('libs/simplebar/simplebar.min.css') }}">
    <link rel="stylesheet" href="{{ asset('libs/flatpickr/flatpickr.min.css') }}">
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
                            <a href="{{ route('admin.home') }}" class="header-logo">
                                <img src="{{ asset('images/brand-logos/logo.png') }}" alt="{{ $companyDisplayName }}"
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
                            <svg xmlns="http://www.w3.org/2000/svg" class="header-link-icon menu-btn-close d-none"
                                width="32" height="32" fill="#000000" viewBox="0 0 256 256">
                                <path
                                    d="M205.66,194.34a8,8,0,0,1-11.32,11.32L128,139.31,61.66,205.66a8,8,0,0,1-11.32-11.32L116.69,128,50.34,61.66A8,8,0,0,1,61.66,50.34L128,116.69l66.34-66.35a8,8,0,0,1,11.32,11.32L139.31,128Z">
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
                            @can('company.edit')
                                <li>
                                    <a href="{{ route('admin.company.edit') }}"
                                        class="dropdown-item d-flex align-items-center py-2 {{ request()->routeIs('admin.company.*') ? 'active' : '' }}">
                                        <i class="ri-building-line me-2 fs-16"></i> Company setup
                                    </a>
                                </li>
                            @endcan
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="mb-0">
                                    @csrf
                                    <button type="submit"
                                        class="dropdown-item d-flex align-items-center w-100 text-start border-0 bg-transparent py-2">
                                        <i class="ri-logout-box-line me-2 fs-16"></i> Log out
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
                <a href="{{ route('admin.home') }}" class="header-logo">
                    <img src="{{ asset('images/brand-logos/logo.png') }}" alt="{{ $companyDisplayName }}"
                        class="desktop-logo" style="max-height:36px;max-width:100%;width:auto;object-fit:contain;">
                </a>
            </div>
            <div class="main-sidebar" id="sidebar-scroll">
                <nav class="main-menu-container nav nav-pills flex-column sub-open">
                    <div class="slide-left" id="slide-left">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24">
                            <path d="M13.293 6.293 7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z"></path>
                        </svg>
                    </div>
                    <ul class="main-menu">
                        @role('Super Admin|Admin')
                            <li class="slide">
                                <a href="{{ route('admin.dashboard') }}"
                                    class="side-menu__item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                                    <i class="ri-dashboard-line side-menu__icon"></i>
                                    <span class="side-menu__label">Dashboard</span>
                                </a>
                            </li>
                        @endrole
                        @can('vendors.view')
                            <li class="slide">
                                <a href="{{ route('admin.vendors.index') }}"
                                    class="side-menu__item {{ request()->routeIs('admin.vendors.*') ? 'active' : '' }}">
                                    <i class="ri-truck-line side-menu__icon"></i>
                                    <span class="side-menu__label">Vendors</span>
                                </a>
                            </li>
                        @endcan
                        @can('customers.view')
                            <li class="slide">
                                <a href="{{ route('admin.customers.index') }}"
                                    class="side-menu__item {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">
                                    <i class="ri-user-3-line side-menu__icon"></i>
                                    <span class="side-menu__label">Customers</span>
                                </a>
                            </li>
                        @endcan
                        @can('inventory.view')
                            <li class="slide">
                                <a href="{{ route('admin.warehouses.index') }}"
                                    class="side-menu__item {{ request()->routeIs('admin.warehouses.*', 'admin.items.*', 'admin.inventory.balances.*', 'admin.inventory.adjust*', 'admin.inventory.transfer*') ? 'active' : '' }}">
                                    <i class="ri-building-4-line side-menu__icon"></i>
                                    <span class="side-menu__label">Inventory</span>
                                </a>
                            </li>
                        @endcan
                        @can('reports.inventory')
                            <li class="slide">
                                <a href="{{ route('admin.inventory.traceability.index') }}"
                                    class="side-menu__item {{ request()->routeIs('admin.inventory.traceability.*') ? 'active' : '' }}">
                                    <i class="ri-barcode-line side-menu__icon"></i>
                                    <span class="side-menu__label">Batch traceability</span>
                                </a>
                            </li>
                        @endcan
                        @can('purchase.pr.create')
                            <li class="slide">
                                <a href="{{ route('admin.purchase.requisitions.index') }}"
                                    class="side-menu__item {{ request()->routeIs('admin.purchase.requisitions.*') ? 'active' : '' }}">
                                    <i class="ri-file-list-3-line side-menu__icon"></i>
                                    <span class="side-menu__label">Requisitions</span>
                                </a>
                            </li>
                        @endcan
                        @if (auth()->user()->can('purchase.po.create') || auth()->user()->can('purchase.po.approve'))
                            <li class="slide">
                                <a href="{{ route('admin.purchase.orders.index') }}"
                                    class="side-menu__item {{ request()->routeIs('admin.purchase.orders.*') ? 'active' : '' }}">
                                    <i class="ri-shopping-cart-2-line side-menu__icon"></i>
                                    <span class="side-menu__label">Purchase orders</span>
                                </a>
                            </li>
                        @endif
                        @if (auth()->user()->can('purchase.grn.create') || auth()->user()->can('inventory.grn'))
                            <li class="slide">
                                <a href="{{ route('admin.purchase.grns.index') }}"
                                    class="side-menu__item {{ request()->routeIs('admin.purchase.grns.*') ? 'active' : '' }}">
                                    <i class="ri-inbox-archive-line side-menu__icon"></i>
                                    <span class="side-menu__label">Goods receipts</span>
                                </a>
                            </li>
                            <li class="slide">
                                <a href="{{ route('admin.purchase.grn-returns.index') }}"
                                    class="side-menu__item {{ request()->routeIs('admin.purchase.grn-returns.*') ? 'active' : '' }}">
                                    <i class="ri-arrow-go-back-line side-menu__icon"></i>
                                    <span class="side-menu__label">GRN returns</span>
                                </a>
                            </li>
                            <li class="slide">
                                <a href="{{ route('admin.purchase.debit-notes.index') }}"
                                    class="side-menu__item {{ request()->routeIs('admin.purchase.debit-notes.*') ? 'active' : '' }}">
                                    <i class="ri-file-reduce-line side-menu__icon"></i>
                                    <span class="side-menu__label">Debit notes</span>
                                </a>
                            </li>
                        @endif
                        @can('sales.quotation.create')
                            <li class="slide">
                                <a href="{{ route('admin.sales.enquiries.index') }}"
                                    class="side-menu__item {{ request()->routeIs('admin.sales.enquiries.*') ? 'active' : '' }}">
                                    <i class="ri-customer-service-2-line side-menu__icon"></i>
                                    <span class="side-menu__label">Enquiries</span>
                                </a>
                            </li>
                            <li class="slide">
                                <a href="{{ route('admin.sales.quotations.index') }}"
                                    class="side-menu__item {{ request()->routeIs('admin.sales.quotations.*') ? 'active' : '' }}">
                                    <i class="ri-price-tag-3-line side-menu__icon"></i>
                                    <span class="side-menu__label">Quotations</span>
                                </a>
                            </li>
                        @endcan
                        @can('customers.edit')
                            <li class="slide">
                                <a href="{{ route('admin.sales.price-lists.index') }}"
                                    class="side-menu__item {{ request()->routeIs('admin.sales.price-lists.*') ? 'active' : '' }}">
                                    <i class="ri-list-check-2 side-menu__icon"></i>
                                    <span class="side-menu__label">Price lists</span>
                                </a>
                            </li>
                        @endcan
                        @if (auth()->user()->can('sales.order.create') || auth()->user()->can('sales.dispatch'))
                            <li class="slide">
                                <a href="{{ route('admin.sales.orders.index') }}"
                                    class="side-menu__item {{ request()->routeIs('admin.sales.orders.*') ? 'active' : '' }}">
                                    <i class="ri-bill-line side-menu__icon"></i>
                                    <span class="side-menu__label">Sales orders</span>
                                </a>
                            </li>
                        @endif
                        @can('sales.invoice.create')
                            <li class="slide">
                                <a href="{{ route('admin.sales.invoices.index') }}"
                                    class="side-menu__item {{ request()->routeIs('admin.sales.invoices.*') ? 'active' : '' }}">
                                    <i class="ri-file-list-3-line side-menu__icon"></i>
                                    <span class="side-menu__label">Invoices</span>
                                </a>
                            </li>
                            <li class="slide">
                                <a href="{{ route('admin.sales.credit-notes.index') }}"
                                    class="side-menu__item {{ request()->routeIs('admin.sales.credit-notes.*') ? 'active' : '' }}">
                                    <i class="ri-refund-2-line side-menu__icon"></i>
                                    <span class="side-menu__label">Credit notes</span>
                                </a>
                            </li>
                        @endcan
                        @can('production.bom.create')
                            <li class="slide">
                                <a href="{{ route('admin.production.boms.index') }}"
                                    class="side-menu__item {{ request()->routeIs('admin.production.boms.*') ? 'active' : '' }}">
                                    <i class="ri-node-tree side-menu__icon"></i>
                                    <span class="side-menu__label">BOM</span>
                                </a>
                            </li>
                        @endcan
                        @if (auth()->user()->can('production.order.create') || auth()->user()->can('production.log'))
                            <li class="slide">
                                <a href="{{ route('admin.production.work-orders.index') }}"
                                    class="side-menu__item {{ request()->routeIs('admin.production.work-orders.*') ? 'active' : '' }}">
                                    <i class="ri-tools-line side-menu__icon"></i>
                                    <span class="side-menu__label">Work orders</span>
                                </a>
                            </li>
                        @endif
                        @if (auth()->user()->can('finance.voucher.create') || auth()->user()->can('finance.reports.view'))
                            <li class="slide">
                                <a href="{{ route('admin.finance.vouchers.index') }}"
                                    class="side-menu__item {{ request()->routeIs('admin.finance.vouchers.*') ? 'active' : '' }}">
                                    <i class="ri-wallet-3-line side-menu__icon"></i>
                                    <span class="side-menu__label">Vouchers</span>
                                </a>
                            </li>
                        @endif
                        @can('finance.reports.view')
                            <li class="slide">
                                <a href="{{ route('admin.finance.chart-of-accounts.index') }}"
                                    class="side-menu__item {{ request()->routeIs('admin.finance.chart-of-accounts.*') ? 'active' : '' }}">
                                    <i class="ri-book-2-line side-menu__icon"></i>
                                    <span class="side-menu__label">Chart of accounts</span>
                                </a>
                            </li>
                        @endcan
                        @can('finance.payment.approve')
                            <li class="slide">
                                <a href="{{ route('admin.finance.payments.index') }}"
                                    class="side-menu__item {{ request()->routeIs('admin.finance.payments.*') ? 'active' : '' }}">
                                    <i class="ri-bank-card-line side-menu__icon"></i>
                                    <span class="side-menu__label">Payments</span>
                                </a>
                            </li>
                        @endcan
                        @can('hr.employee.manage')
                            <li class="slide">
                                <a href="{{ route('admin.hr.employees.index') }}"
                                    class="side-menu__item {{ request()->routeIs('admin.hr.employees.*') ? 'active' : '' }}">
                                    <i class="ri-team-line side-menu__icon"></i>
                                    <span class="side-menu__label">Employees</span>
                                </a>
                            </li>
                            <li class="slide">
                                <a href="{{ route('admin.hr.departments.index') }}"
                                    class="side-menu__item {{ request()->routeIs('admin.hr.departments.*') ? 'active' : '' }}">
                                    <i class="ri-building-line side-menu__icon"></i>
                                    <span class="side-menu__label">Departments</span>
                                </a>
                            </li>
                            <li class="slide">
                                <a href="{{ route('admin.hr.designations.index') }}"
                                    class="side-menu__item {{ request()->routeIs('admin.hr.designations.*') ? 'active' : '' }}">
                                    <i class="ri-briefcase-line side-menu__icon"></i>
                                    <span class="side-menu__label">Designations</span>
                                </a>
                            </li>
                            <li class="slide">
                                <a href="{{ route('admin.hr.payroll-settings.edit') }}"
                                    class="side-menu__item {{ request()->routeIs('admin.hr.payroll-settings.*') ? 'active' : '' }}">
                                    <i class="ri-settings-3-line side-menu__icon"></i>
                                    <span class="side-menu__label">Payroll rules</span>
                                </a>
                            </li>
                            <li class="slide">
                                <a href="{{ route('admin.hr.allowance-types.index') }}"
                                    class="side-menu__item {{ request()->routeIs('admin.hr.allowance-types.*') ? 'active' : '' }}">
                                    <i class="ri-wallet-line side-menu__icon"></i>
                                    <span class="side-menu__label">Allowance types</span>
                                </a>
                            </li>
                        @endcan
                        @can('hr.attendance.mark')
                            <li class="slide">
                                <a href="{{ route('admin.hr.attendance.index') }}"
                                    class="side-menu__item {{ request()->routeIs('admin.hr.attendance.*') ? 'active' : '' }}">
                                    <i class="ri-calendar-check-line side-menu__icon"></i>
                                    <span class="side-menu__label">Attendance</span>
                                </a>
                            </li>
                        @endcan
                        @can('hr.payroll.run')
                            <li class="slide">
                                <a href="{{ route('admin.hr.payroll-runs.index') }}"
                                    class="side-menu__item {{ request()->routeIs('admin.hr.payroll-runs.*') ? 'active' : '' }}">
                                    <i class="ri-money-cny-circle-line side-menu__icon"></i>
                                    <span class="side-menu__label">Payroll</span>
                                </a>
                            </li>
                        @endcan
                        @can('hr.employee.manage')
                            <li class="slide">
                                <a href="{{ route('admin.hr.leave.index') }}"
                                    class="side-menu__item {{ request()->routeIs('admin.hr.leave.*') ? 'active' : '' }}">
                                    <i class="ri-calendar-event-line side-menu__icon"></i>
                                    <span class="side-menu__label">Leave</span>
                                </a>
                            </li>
                        @endcan
                        @if (auth()->user()->can('reports.sales') ||
                                auth()->user()->can('reports.purchase') ||
                                auth()->user()->can('reports.inventory') ||
                                auth()->user()->can('reports.finance') ||
                                auth()->user()->can('hr.employee.manage'))
                            <li class="slide">
                                <a href="{{ route('admin.reports.index') }}"
                                    class="side-menu__item {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                                    <i class="ri-bar-chart-2-line side-menu__icon"></i>
                                    <span class="side-menu__label">Reports</span>
                                </a>
                            </li>
                        @endif
                        @can('users.view')
                            <li class="slide">
                                <a href="{{ route('admin.users.index') }}"
                                    class="side-menu__item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                                    <i class="ri-group-line side-menu__icon"></i>
                                    <span class="side-menu__label">Users</span>
                                </a>
                            </li>
                        @endcan
                        @can('audit.view')
                            <li class="slide">
                                <a href="{{ route('admin.audit-logs.index') }}"
                                    class="side-menu__item {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}">
                                    <i class="ri-history-line side-menu__icon"></i>
                                    <span class="side-menu__label">Audit log</span>
                                </a>
                            </li>
                        @endcan
                    </ul>
                    <div class="slide-right" id="slide-right">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24">
                            <path d="M10.707 17.707 16.414 12l-5.707-5.707-1.414 1.414L13.586 12l-4.293 4.293z"></path>
                        </svg>
                    </div>
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
    <script src="{{ asset('libs/flatpickr/flatpickr.min.js') }}"></script>
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
