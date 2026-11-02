"""
Lancement du service IA dans les conteneurs (Docker et Railway).

uvicorn n'écoute que sur une seule adresse : « 0.0.0.0 » refuse l'IPv6 et « :: » refuse
l'IPv4. Le réseau privé de Railway (ai.railway.internal) passe par IPv6, docker-compose
par IPv4 : on ouvre donc une socket double pile, ou IPv4 seule si l'IPv6 est indisponible.

    python serveur.py            (port 8001, ou variable AI_PORT)
"""

from __future__ import annotations

import os
import socket

import uvicorn

PORT = int(os.environ.get("AI_PORT", "8001"))


def ouvrir_socket(port: int) -> socket.socket:
    try:
        sock = socket.socket(socket.AF_INET6, socket.SOCK_STREAM)
        sock.setsockopt(socket.IPPROTO_IPV6, socket.IPV6_V6ONLY, 0)
        sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        sock.bind(("::", port))
    except OSError:
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        sock.bind(("0.0.0.0", port))

    sock.listen(2048)
    sock.set_inheritable(True)
    return sock


if __name__ == "__main__":
    sock = ouvrir_socket(PORT)
    print(f"Service IA en écoute sur {sock.getsockname()[0]}:{PORT}", flush=True)
    uvicorn.Server(uvicorn.Config("app:app", log_level="info")).run(sockets=[sock])
