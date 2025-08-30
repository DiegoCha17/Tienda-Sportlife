<?php require_once "Conexionbd.php"; ?>
<?php session_start(); ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Productos</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="estilos.css" rel="stylesheet">
</head>
<body class="admin-page">
    <div class="container">
    <div class="header">
    <h1><i class="fas fa-store"></i> Panel de Administración</h1>
    <p>Gestiona todos tus productos de manera eficiente</p>

    <div class="header-buttons">
        <a href="ventas.php" class="btn btn-secondary">
            <i class="fas fa-receipt"></i> Registro de Ventas
        </a>
        
        <a href="logout.php" class="btn btn-danger">
            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
        </a>
    </div>
</div>


        <div class="stats">
            <div class="stat-card">
                <div class="stat-number" id="totalProducts">-</div>
                <div class="stat-label">Total Productos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="totalValue">$-</div>
                <div class="stat-label">Valor Total</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="categories">-</div>
                <div class="stat-label">Categorías</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="lowStock">-</div>
                <div class="stat-label">Stock Bajo</div>
            </div>
        </div>

        <div class="controls">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Buscar productos...">
                <i class="fas fa-search"></i>
            </div>
            <button class="btn btn-primary" onclick="openModal('add')">
                <i class="fas fa-plus"></i> Agregar Producto
            </button>
        </div>

        <div class="products-grid" id="productsGrid">
            <div class="empty-state">
                <i class="fas fa-spinner fa-spin"></i>
                <h3>Cargando productos...</h3>
                <p>Por favor espera un momento</p>
            </div>
        </div>
    </div>

    <!-- Modal para Agregar/Editar Producto -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Agregar Producto</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="productForm">
                    <input type="hidden" id="productId" name="id">
                    
                    <div class="form-group">
                        <label for="productName">Nombre del Producto</label>
                        <input type="text" id="productName" name="nombre" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="productPrice">Precio</label>
                        <input type="number" id="productPrice" name="precio" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="productCategory">Categoría</label>
                        <select id="productCategory" name="id_categoria" required>
                            <option value="">Seleccionar categoría</option>
                            <!-- Las categorías se cargarán dinámicamente -->
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="productBrand">Marca</label>
                        <select id="productBrand" name="id_marca" required>
                            <option value="">Seleccionar marca</option>
                            <!-- Las marcas se cargarán dinámicamente -->
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="productQuantity">Cantidad</label>
                        <input type="number" id="productQuantity" name="cantidad" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="productImage">Imagen (archivo)</label>
                        <input type="file" id="productImage" name="imagen" accept="image/*">
                        <small style="color: #666;">Formatos permitidos: JPG, PNG, WEBP</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="productStatus">Estado</label>
                        <select id="productStatus" name="activo" required>
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-success" id="submitBtn">
                            <i class="fas fa-save"></i> Guardar Producto
                        </button>
                        <button type="button" class="btn btn-danger" onclick="closeModal()">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

  
    <script src="administrador.js"></script>
</body>
</html>