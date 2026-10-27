<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Vehiculo;
use App\Models\VehiculoMantenimiento;
use App\Models\OperacionReciproca;
use App\Models\Alimento;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Roles ───
        $roles = ['super-admin', 'admin', 'contador', 'asistente', 'visualizador'];
        foreach ($roles as $rol) Role::firstOrCreate(['name' => $rol]);

        // ─── Usuarios ───
        $admin = User::firstOrCreate(['email' => 'admin@inpe-orc.gob.pe'], [
            'name' => 'Administrador', 'apellidos' => 'Sistema', 'dni' => '00000001',
            'cargo' => 'Administrador del Sistema', 'area' => 'TI',
            'estado' => 'activo', 'password' => Hash::make('Admin2024*'),
        ]);
        $admin->assignRole('super-admin');

        $contador = User::firstOrCreate(['email' => 'contador@inpe-orc.gob.pe'], [
            'name' => 'María', 'apellidos' => 'García López', 'dni' => '45678901',
            'cargo' => 'Contador Público', 'area' => 'Administración',
            'estado' => 'activo', 'password' => Hash::make('Contador2024*'),
        ]);
        $contador->assignRole('contador');

        $asistente = User::firstOrCreate(['email' => 'asistente@inpe-orc.gob.pe'], [
            'name' => 'Carlos', 'apellidos' => 'Quispe Mamani', 'dni' => '72345678',
            'cargo' => 'Técnico Administrativo', 'area' => 'Administración',
            'estado' => 'activo', 'password' => Hash::make('Asistente2024*'),
        ]);
        $asistente->assignRole('asistente');

        // ─── Vehículos ───
        $vehiculosData = [
            ['placa'=>'ABC-123','marca'=>'Toyota','modelo'=>'Hilux','anio_fabricacion'=>2019,'tipo'=>'camioneta','combustible'=>'diesel','estado'=>'operativo','color'=>'Blanco','kilometraje_actual'=>45000,'valor_adquisicion'=>85000,'fecha_soat'=>now()->addDays(20),'fecha_revision_tecnica'=>now()->addDays(45)],
            ['placa'=>'DEF-456','marca'=>'Nissan','modelo'=>'Frontier','anio_fabricacion'=>2018,'tipo'=>'camioneta','combustible'=>'diesel','estado'=>'disponible','color'=>'Plata','kilometraje_actual'=>62000,'valor_adquisicion'=>78000,'fecha_soat'=>now()->addMonths(3),'fecha_revision_tecnica'=>now()->addMonths(2)],
            ['placa'=>'GHI-789','marca'=>'Hyundai','modelo'=>'H100','anio_fabricacion'=>2020,'tipo'=>'furgon','combustible'=>'gasolina','estado'=>'mantenimiento','color'=>'Blanco','kilometraje_actual'=>38000,'valor_adquisicion'=>55000,'fecha_soat'=>now()->addMonths(6)],
            ['placa'=>'JKL-012','marca'=>'Honda','modelo'=>'CG150','anio_fabricacion'=>2021,'tipo'=>'moto','combustible'=>'gasolina','estado'=>'operativo','color'=>'Rojo','kilometraje_actual'=>12000,'valor_adquisicion'=>8500,'fecha_soat'=>now()->addMonths(8)],
            ['placa'=>'MNO-345','marca'=>'Ford','modelo'=>'F-350','anio_fabricacion'=>2017,'tipo'=>'camion','combustible'=>'diesel','estado'=>'baja','color'=>'Verde','kilometraje_actual'=>189000,'valor_adquisicion'=>120000],
        ];
        foreach ($vehiculosData as $vd) {
            Vehiculo::firstOrCreate(['placa' => $vd['placa']], array_merge($vd, ['creado_por' => $admin->id]));
        }

        // ─── Mantenimientos ───
        $v1 = Vehiculo::where('placa', 'ABC-123')->first();
        $v3 = Vehiculo::where('placa', 'GHI-789')->first();
        if ($v1) {
            VehiculoMantenimiento::firstOrCreate(['vehiculo_id' => $v1->id, 'descripcion' => 'Cambio de aceite y filtros'], [
                'tipo_mantenimiento' => 'preventivo', 'fecha_programada' => now()->subMonths(2),
                'fecha_realizado' => now()->subMonths(2), 'costo_estimado' => 350, 'costo_real' => 320,
                'total' => 320, 'taller' => 'Taller Central INPE', 'estado' => 'completado',
                'orden_servicio' => 'OS-2024-001', 'registro_siaf' => 12345,
                'registrado_por' => $admin->id,
            ]);
        }
        if ($v3) {
            VehiculoMantenimiento::firstOrCreate(['vehiculo_id' => $v3->id, 'descripcion' => 'Revisión de frenos y suspensión'], [
                'tipo_mantenimiento' => 'correctivo', 'fecha_programada' => now()->subDays(3),
                'costo_estimado' => 850, 'estado' => 'en_proceso',
                'taller' => 'Mecánica San Martín', 'registrado_por' => $admin->id,
            ]);
        }

        // ─── Operaciones Recíprocas ───
        $opData = [
            ['tipo_servicio'=>'energia_electrica','entidad_proveedora'=>'Electrocentro S.A.','ruc_entidad'=>'20501466498','razon_social'=>'Empresa Regional de Servicio Público de Electricidad del Centro S.A.','servicio_prestado'=>'Suministro de energía eléctrica','numero_factura'=>'F001-00123','factura_serie'=>'F001','monto'=>1850.00,'igv'=>333.00,'total'=>2183.00,'base_imponible'=>1850.00,'fecha_emision'=>now()->subDays(15),'fecha_vencimiento'=>now()->addDays(5),'periodo_mes'=>now()->month,'periodo_anio'=>now()->year,'penal_pertenece'=>'HYO','estado'=>'pendiente','orden_servicio'=>'OS-2024-045','registro_siaf'=>22001],
            ['tipo_servicio'=>'agua_saneamiento','entidad_proveedora'=>'SEDAM Huancayo','ruc_entidad'=>'20131680327','razon_social'=>'Servicio de Agua Potable y Alcantarillado de Huancayo S.A.','servicio_prestado'=>'Servicio de agua potable','numero_factura'=>'B001-00456','factura_serie'=>'B001','monto'=>420.00,'igv'=>0,'total'=>420.00,'base_imponible'=>420.00,'fecha_emision'=>now()->subDays(20),'fecha_vencimiento'=>now()->subDays(5),'periodo_mes'=>now()->month,'periodo_anio'=>now()->year,'penal_pertenece'=>'HYO','estado'=>'vencido','orden_servicio'=>'OS-2024-038','registro_siaf'=>22002],
            ['tipo_servicio'=>'internet','entidad_proveedora'=>'Claro Perú S.A.C.','ruc_entidad'=>'20514895223','razon_social'=>'América Móvil Perú S.A.C.','servicio_prestado'=>'Servicio de Internet empresarial','numero_factura'=>'F001-00789','factura_serie'=>'F001','monto'=>380.00,'igv'=>68.40,'total'=>448.40,'base_imponible'=>380.00,'fecha_emision'=>now()->subMonths(1),'fecha_vencimiento'=>now()->subDays(20),'periodo_mes'=>now()->subMonth()->month,'periodo_anio'=>now()->year,'penal_pertenece'=>'HYO','estado'=>'pagado','metodo_pago'=>'transferencia','numero_voucher'=>'TRF-2024-001','fecha_pago'=>now()->subDays(18),'orden_servicio'=>'OS-2024-029','registro_siaf'=>21998],
        ];
        foreach ($opData as $op) {
            OperacionReciproca::firstOrCreate(
                ['numero_factura' => $op['numero_factura'], 'tipo_servicio' => $op['tipo_servicio']],
                array_merge($op, ['registrado_por' => $contador->id])
            );
        }

        // ─── Alimentos ───
        $aliData = [
            ['proveedor_ruc'=>'20601234567','proveedor_razon_social'=>'Distribuidora Alimentaria San José S.A.C.','proveedor_nombre'=>'AlimSanJosé','numero_contrato'=>'CONT-ALI-2024-001','penal'=>'HYO','periodo_inicio'=>now()->subMonths(2),'periodo_fin'=>now()->addMonths(4),'orden_servicio'=>'OS-ALI-001','fecha_orden_servicio'=>now()->subMonths(2),'registro_siaf'=>'33001','factura_serie'=>'F001','factura_numero'=>'00001234','fecha_emision'=>now()->subMonths(2),'base_imponible'=>45000,'igv'=>8100,'otros'=>0,'total'=>53100,'estado'=>'en_proceso','garantia_descripcion'=>'Carta fianza N° 0012345','garantia_monto'=>5310,'garantia_vencimiento'=>now()->addMonths(5)],
            ['proveedor_ruc'=>'20712345678','proveedor_razon_social'=>'Corporación de Alimentos del Centro E.I.R.L.','proveedor_nombre'=>'CorpAlimentos','numero_contrato'=>'CONT-ALI-2024-002','penal'=>'HVCA','periodo_inicio'=>now()->subMonth(),'periodo_fin'=>now()->addMonths(2),'orden_servicio'=>'OS-ALI-002','fecha_orden_servicio'=>now()->subMonth(),'registro_siaf'=>'33002','factura_serie'=>'E001','factura_numero'=>'00000456','fecha_emision'=>now()->subMonth(),'base_imponible'=>28000,'igv'=>5040,'otros'=>0,'total'=>33040,'estado'=>'pendiente','garantia_descripcion'=>'Carta fianza N° 0012346','garantia_monto'=>3304,'garantia_vencimiento'=>now()->addMonths(3)],
            ['proveedor_ruc'=>'20456789012','proveedor_razon_social'=>'Empresa Proveedora de Alimentos Nacionales S.A.','proveedor_nombre'=>'EPAN','numero_contrato'=>'CONT-ALI-2024-003','penal'=>'AYACUCHO','periodo_inicio'=>now()->subMonths(3),'periodo_fin'=>now()->subMonths(1),'orden_servicio'=>'OS-ALI-003','fecha_orden_servicio'=>now()->subMonths(3),'registro_siaf'=>'33003','factura_serie'=>'F001','factura_numero'=>'00000789','fecha_emision'=>now()->subMonths(3),'base_imponible'=>38000,'igv'=>6840,'otros'=>0,'total'=>44840,'estado'=>'pagado','fecha_pago'=>now()->subMonths(1),'metodo_pago'=>'transferencia','numero_voucher'=>'TRF-ALI-001'],
        ];
        foreach ($aliData as $ali) {
            Alimento::firstOrCreate(
                ['proveedor_ruc' => $ali['proveedor_ruc'], 'numero_contrato' => $ali['numero_contrato']],
                array_merge($ali, ['registrado_por' => $contador->id])
            );
        }
    }
}
