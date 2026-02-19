FROM node:20-alpine

# Use pnpm
RUN npm install -g pnpm

WORKDIR /app

# Install startup script and make it executable
COPY backend/docker/node/start-frontend.sh /usr/local/bin/start-frontend
RUN chmod +x /usr/local/bin/start-frontend

# Create a non-root user and switch to it
RUN addgroup -S appgroup && adduser -S appuser -G appgroup
USER appuser
