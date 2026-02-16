FROM node:20-alpine

# Install pnpm
RUN npm install -g pnpm

# Set working directory
WORKDIR /app

# Install startup script
COPY docker/node/start-frontend.sh /usr/local/bin/start-frontend
RUN chmod +x /usr/local/bin/start-frontend

# Expose port
EXPOSE 3000

CMD ["start-frontend"]
