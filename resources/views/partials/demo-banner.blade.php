{{-- Banner "Cuenta de demostración" — se muestra a usuarios con email demo.f2.*
     Los datos de estas cuentas se EXCLUYEN de reportes contables. --}}
@auth
    @if (auth()->user()->esDemoFase2())
        <div role="status"
             style="background-color: #FEF3C7; border-bottom: 1px solid #FCD34D; color: #78350F; padding: 0.5rem 1rem; text-align: center; font-size: 0.8125rem; font-weight: 500; font-family: 'Inter', system-ui, sans-serif;">
            <span aria-hidden="true">🧪</span>
            {{ __('Cuenta de demostración — los datos no afectan reportes contables ni cobros reales.') }}
        </div>
    @endif
@endauth
