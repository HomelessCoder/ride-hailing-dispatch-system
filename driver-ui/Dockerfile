FROM node:20-alpine

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm ci

# Copy application files
COPY . .

# Expose port 4200
EXPOSE 4200

# Start development server
CMD ["npm", "start"]
