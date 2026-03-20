/**
 * VIZENGO - Sistema de Gestión de Pedidos
 * Archivo JavaScript principal para conexión con API
 */

const API_BASE_URL = 'backend/api';

// ========================================
// AUTENTICACIÓN
// ========================================

async function login(usuario, password) {
    try {
        const response = await fetch(`${API_BASE_URL}/auth.php?accion=login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ usuario, password })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Guardar en sessionStorage
            sessionStorage.setItem('vz_usuario_id', data.user.id);
            sessionStorage.setItem('vz_nombre_usuario', data.user.nombre_usuario);
            sessionStorage.setItem('vz_nombre_completo', data.user.nombre_completo);
            sessionStorage.setItem('vz_rol', data.user.rol);
        }
        
        return data;
    } catch (error) {
        console.error('Error en login:', error);
        return { success: false, error: 'Error de conexión' };
    }
}

async function logout() {
    try {
        await fetch(`${API_BASE_URL}/auth.php?accion=logout`);
        sessionStorage.clear();
        window.location.href = 'index.html';
    } catch (error) {
        console.error('Error en logout:', error);
    }
}

async function verificarSesion() {
    try {
        const response = await fetch(`${API_BASE_URL}/auth.php?accion=verificar`);
        const data = await response.json();
        
        if (!data.success) {
            sessionStorage.clear();
            if (window.location.pathname.indexOf('index.html') === -1) {
                window.location.href = 'index.html';
            }
        }
        
        return data;
    } catch (error) {
        console.error('Error al verificar sesión:', error);
        return { success: false };
    }
}

function obtenerUsuarioDeSesion() {
    return {
        id: sessionStorage.getItem('vz_usuario_id'),
        nombre_usuario: sessionStorage.getItem('vz_nombre_usuario'),
        nombre_completo: sessionStorage.getItem('vz_nombre_completo'),
        rol: sessionStorage.getItem('vz_rol')
    };
}

// ========================================
// PEDIDOS
// ========================================

async function listarPedidos(filtros = {}) {
    try {
        const params = new URLSearchParams();
        params.set('accion', 'listar');
        
        if (filtros.estado) params.set('estado', filtros.estado);
        if (filtros.filtro) params.set('filtro', filtros.filtro);
        
        const response = await fetch(`${API_BASE_URL}/pedidos.php?${params}`);
        return await response.json();
    } catch (error) {
        console.error('Error al listar pedidos:', error);
        return { success: false, error: 'Error de conexión' };
    }
}

async function obtenerPedido(id) {
    try {
        const response = await fetch(`${API_BASE_URL}/pedidos.php?accion=obtener&id=${id}`);
        return await response.json();
    } catch (error) {
        console.error('Error al obtener pedido:', error);
        return { success: false, error: 'Error de conexión' };
    }
}

async function crearPedido(pedidoData) {
    try {
        const response = await fetch(`${API_BASE_URL}/pedidos.php?accion=crear`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(pedidoData)
        });
        return await response.json();
    } catch (error) {
        console.error('Error al crear pedido:', error);
        return { success: false, error: 'Error de conexión' };
    }
}

async function actualizarPedido(pedidoData) {
    try {
        const response = await fetch(`${API_BASE_URL}/pedidos.php?accion=actualizar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(pedidoData)
        });
        return await response.json();
    } catch (error) {
        console.error('Error al actualizar pedido:', error);
        return { success: false, error: 'Error de conexión' };
    }
}

async function actualizarEstadoPedido(id, estado, observaciones = '') {
    try {
        const response = await fetch(`${API_BASE_URL}/pedidos.php?accion=actualizar_estado`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id, estado, observaciones })
        });
        return await response.json();
    } catch (error) {
        console.error('Error al actualizar estado:', error);
        return { success: false, error: 'Error de conexión' };
    }
}

// ========================================
// INTEGRANTES
// ========================================

async function listarIntegrantes(pedidoId) {
    try {
        const response = await fetch(`${API_BASE_URL}/integrantes.php?accion=listar&pedido_id=${pedidoId}`);
        return await response.json();
    } catch (error) {
        console.error('Error al listar integrantes:', error);
        return { success: false, error: 'Error de conexión' };
    }
}

async function agregarIntegrante(integranteData) {
    try {
        const response = await fetch(`${API_BASE_URL}/integrantes.php?accion=agregar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(integranteData)
        });
        return await response.json();
    } catch (error) {
        console.error('Error al agregar integrante:', error);
        return { success: false, error: 'Error de conexión' };
    }
}

async function eliminarIntegrante(id) {
    try {
        const response = await fetch(`${API_BASE_URL}/integrantes.php?accion=eliminar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id })
        });
        return await response.json();
    } catch (error) {
        console.error('Error al eliminar integrante:', error);
        return { success: false, error: 'Error de conexión' };
    }
}

// ========================================
// DISEÑOS
// ========================================

async function subirDiseno(formData) {
    try {
        const response = await fetch(`${API_BASE_URL}/disenos.php?accion=subir`, {
            method: 'POST',
            body: formData
        });
        return await response.json();
    } catch (error) {
        console.error('Error al subir diseño:', error);
        return { success: false, error: 'Error de conexión' };
    }
}

// ========================================
// UTILIDADES
// ========================================

function mostrarMensaje(mensaje, tipo = 'info') {
    const colores = {
        info: '#2B4FFF',
        success: '#06d6a0',
        warning: '#f59e0b',
        error: '#ef476f'
    };
    
    // Implementación simple - puede mejorarse con un toast/notification system
    alert(`[${tipo.toUpperCase()}] ${mensaje}`);
}

function formatearMoneda(cantidad) {
    return new Intl.NumberFormat('es-EC', {
        style: 'currency',
        currency: 'USD'
    }).format(cantidad);
}

function formatearFecha(fecha) {
    if (!fecha) return '';
    return new Date(fecha).toLocaleDateString('es-EC', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// ========================================
// INICIALIZACIÓN
// ========================================

// Verificar sesión al cargar cualquier página (excepto index.html)
document.addEventListener('DOMContentLoaded', async () => {
    if (window.location.pathname.indexOf('index.html') === -1 && 
        window.location.pathname.indexOf('login') === -1) {
        await verificarSesion();
        inicializarInterfaz();
    }
});

function inicializarInterfaz() {
    const usuario = obtenerUsuarioDeSesion();
    
    if (!usuario || !usuario.rol) return;
    
    // Actualizar información de usuario en la interfaz
    const userNameEl = document.getElementById('userName');
    const userRoleEl = document.getElementById('userRole');
    const userAvatarEl = document.getElementById('userAvatar');
    
    if (userNameEl) userNameEl.textContent = usuario.nombre_completo || usuario.nombre_usuario;
    if (userRoleEl) userRoleEl.textContent = traducirRol(usuario.rol);
    if (userAvatarEl) userAvatarEl.textContent = (usuario.nombre_completo || usuario.nombre_usuario)[0].toUpperCase();
    
    // Mostrar/ocultar elementos según rol
    gestionarPermisosPorRol(usuario.rol);
}

function traducirRol(rol) {
    const traducciones = {
        'vendedor': 'Vendedor',
        'disenador': 'Diseñador',
        'administrador': 'Administrador'
    };
    return traducciones[rol] || rol;
}

function gestionarPermisosPorRol(rol) {
    // Elementos específicos por rol
    const elementosVendedor = [
        'nav-vendedor-label',
        'nav-ingreso',
        'nav-integrantes',
        'nav-entrega'
    ];
    
    const elementosDisenador = [
        'nav-disenador-label',
        'nav-diseno',
        'nav-planchado',
        'nav-costura'
    ];
    
    const elementosAdmin = [
        'nav-usuarios'
    ];
    
    // Ocultar todos primero
    [...elementosVendedor, ...elementosDisenador, ...elementosAdmin].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });
    
    // Mostrar según rol
    if (rol === 'vendedor' || rol === 'administrador') {
        elementosVendedor.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.display = (rol === 'administrador') ? 'flex' : 'flex';
        });
    }
    
    if (rol === 'disenador' || rol === 'administrador') {
        elementosDisenador.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.display = 'flex';
        });
    }
    
    if (rol === 'administrador') {
        elementosAdmin.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.display = 'flex';
        });
    }
}

// Exportar funciones para uso global
window.vizengo = {
    login,
    logout,
    verificarSesion,
    obtenerUsuarioDeSesion,
    listarPedidos,
    obtenerPedido,
    crearPedido,
    actualizarPedido,
    actualizarEstadoPedido,
    listarIntegrantes,
    agregarIntegrante,
    eliminarIntegrante,
    subirDiseno,
    mostrarMensaje,
    formatearMoneda,
    formatearFecha
};
