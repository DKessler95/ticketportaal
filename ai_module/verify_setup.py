"""
Verification script for RAG AI Development Environment
This script verifies that all core dependencies are installed correctly.
"""

import sys
import os

def verify_installation():
    """Verify all core dependencies are installed and working"""
    
    print("=" * 70)
    print("RAG AI Development Environment Verification")
    print("=" * 70)
    print()
    
    # Check Python version
    print(f"Python Version: {sys.version}")
    print(f"Python Executable: {sys.executable}")
    print()
    
    # Check core dependencies
    dependencies = {
        'chromadb': 'ChromaDB',
        'sentence_transformers': 'Sentence Transformers',
        'fastapi': 'FastAPI',
        'uvicorn': 'Uvicorn',
        'mysql.connector': 'MySQL Connector',
        'pydantic': 'Pydantic',
        'requests': 'Requests',
        'dotenv': 'Python Dotenv',
        'torch': 'PyTorch',
        'transformers': 'Transformers'
    }
    
    print("Checking Dependencies:")
    print("-" * 70)
    
    all_ok = True
    for module_name, display_name in dependencies.items():
        try:
            module = __import__(module_name)
            version = getattr(module, '__version__', 'Unknown')
            print(f"✓ {display_name:30} {version}")
        except ImportError as e:
            print(f"✗ {display_name:30} NOT INSTALLED")
            all_ok = False
    
    print()
    
    # Check directory structure
    print("Checking Directory Structure:")
    print("-" * 70)
    
    base_dir = os.path.dirname(os.path.abspath(__file__))
    required_dirs = [
        'venv',
        'scripts',
        'logs',
        'chromadb_data',
        'models',
        'backups'
    ]
    
    for dir_name in required_dirs:
        dir_path = os.path.join(base_dir, dir_name)
        if os.path.exists(dir_path):
            print(f"✓ {dir_name:30} EXISTS")
        else:
            print(f"✗ {dir_name:30} MISSING")
            all_ok = False
    
    print()
    print("=" * 70)
    
    if all_ok:
        print("✓ All checks passed! Environment is ready.")
        print()
        print("Next Steps:")
        print("  1. Install Ollama (Task 2)")
        print("  2. Configure Ollama as Windows Service (Task 3)")
        print("  3. Create Sync Pipeline (Task 4)")
    else:
        print("✗ Some checks failed. Please review the output above.")
    
    print("=" * 70)
    
    return all_ok

if __name__ == "__main__":
    success = verify_installation()
    sys.exit(0 if success else 1)
