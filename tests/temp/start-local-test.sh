#!/bin/bash
# Quick local WordPress test setup script

echo "🚀 SEO AI Meta Generator - Local Testing Setup"
echo "==============================================="
echo ""

# Check if Docker is available
if command -v docker &> /dev/null; then
    echo "✅ Docker found"
    
    # Check if docker-compose is available
    if command -v docker-compose &> /dev/null || docker compose version &> /dev/null; then
        echo "✅ Docker Compose found"
        echo ""
        echo "Starting WordPress with Docker..."
        echo ""
        
        # Start services
        if docker compose version &> /dev/null; then
            docker compose up -d
        else
            docker-compose up -d
        fi
        
        echo ""
        echo "⏳ Waiting for services to start..."
        sleep 10
        
        echo ""
        echo "✅ WordPress should be available at: http://localhost:8080"
        echo ""
        echo "📋 Next steps:"
        echo "1. Open http://localhost:8080 in your browser"
        echo "2. Complete WordPress installation"
        echo "3. Plugin is already installed in: wp-content/plugins/seo-ai-meta-generator"
        echo "4. Activate the plugin in WordPress Admin > Plugins"
        echo ""
        echo "To stop: docker compose down"
        echo "To view logs: docker compose logs -f"
        
    else
        echo "❌ Docker Compose not found"
        echo "Please install Docker Compose or use Local by Flywheel"
    fi
else
    echo "❌ Docker not found"
    echo ""
    echo "Please choose one of these options:"
    echo "1. Install Docker Desktop: https://www.docker.com/products/docker-desktop"
    echo "2. Use Local by Flywheel: https://localwp.com/"
    echo "3. Manual setup (see LOCAL_TESTING_SETUP.md)"
    echo ""
    echo "Alternatively, you can:"
    echo "- Upload the plugin zip to an existing WordPress installation"
    echo "- Use the zip file: ../seo-ai-meta-generator-1.0.0.zip"
fi

echo ""
echo "📦 Distribution zip created: ../seo-ai-meta-generator-1.0.0.zip"
echo "📖 See LOCAL_TESTING_SETUP.md for detailed testing guide"

