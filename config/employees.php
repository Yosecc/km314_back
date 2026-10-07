<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Vigencia de la documentación de trabajadores
    |--------------------------------------------------------------------------
    |
    | Define cada cuántos meses debe renovarse la documentación cargada para
    | un trabajador. Se usa al crear y reverificar su ficha.
    |
    */
    'documentation_renewal_months' => max(1, (int) env('EMPLOYEE_DOCUMENTATION_RENEWAL_MONTHS', 4)),
];
