FROM node:20-alpine

# Install pnpm
RUN npm install -g pnpm

# Set working directory
WORKDIR /app

# Copy package.json and pnpm-lock.yaml if they exist
# COPY package.json pnpm-lock.yaml* ./

# Install dependencies
# RUN pnpm install

# Copy source code
# COPY . .

# Expose port
EXPOSE 3000

CMD ["pnpm", "dev"]
