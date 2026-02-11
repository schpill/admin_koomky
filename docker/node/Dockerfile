FROM node:20-alpine

# Install pnpm
RUN npm install -g pnpm@latest

# Set working directory
WORKDIR /app

# Copy package files first (for better layer caching)
COPY frontend/package.json frontend/pnpm-lock.yaml ./

# Install dependencies
RUN pnpm install --frozen-lockfile

# Copy application
COPY frontend/ .

# Expose port
EXPOSE 3000

# Set user
RUN chown -R node:node /app
USER node

# Start dev server
CMD ["pnpm", "dev"]
